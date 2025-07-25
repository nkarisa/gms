variable "lb_target_group_name" {
  type    = string
  default = "tg"
}

locals {
  target_groups = [
    "green",
    "blue",
  ]
}

resource "aws_lb_target_group" "tg" {

  count = var.app_environment == "prod" ? length(local.target_groups) : 0

  name        = "${var.app_name}-${element(local.target_groups, count.index)}"
  port        = 443
  protocol    = "HTTP"
  target_type = "instance"
  vpc_id      = var.vpc_id
  health_check {
    matcher = "200,301,302,404"
    path    = "/"
  }

}

# Create a listener rule to associate the target group with the 443 listener
resource "aws_lb_listener_rule" "green_https_forward_rule" {

  count = var.app_environment == "prod" ? 1 : 0

  listener_arn = data.aws_lb_listener.safina_listener_https_443.arn
  priority     = 0 # Choose a unique priority for your rule

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.tg[0].arn
  }

  condition {
    path_pattern {
      values = ["/"] # This rule applies to all paths. Adjust as needed.
    }
  }
}

resource "aws_lb_listener_rule" "blue_https_forward_rule" {

  count = var.app_environment == "prod" ? 1 : 0

  listener_arn = data.aws_lb_listener.safina_listener_https_443.arn
  priority     = 100 # Choose a unique priority for your rule

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.tg[1].arn
  }

  condition {
    path_pattern {
      values = ["/"] # This rule applies to all paths. Adjust as needed.
    }
  }
}

# Create the AWS ECS Service 
resource "aws_ecs_service" "new_ecs_service_prod" {

  count = var.app_environment == "prod" ? 1 : 0
  
  name            = var.service_name
  cluster         = data.aws_ecs_cluster.safina_app_cluster.id
  task_definition = aws_ecs_task_definition.task_definition.arn
  desired_count   = var.desired_count
  launch_type     = "FARGATE"
  deployment_minimum_healthy_percent = 50
  deployment_maximum_percent = 100

  # To be removed later. This is just present during development work.
  enable_execute_command = true

  # This is the key argument for waiting for stability
  wait_for_steady_state = true 

  network_configuration {
    subnets         = data.aws_subnets.selected_subnets.ids
    security_groups = var.security_group_ids
    # This should typically be set to true for services running in awsvpc mode
    assign_public_ip = false # Or false, depending on your network design (e.g., if you have a NAT Gateway)
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.tg[0].arn // data.aws_lb_target_group.safina_ecs_tg.arn
    container_name   = var.container_name # Must match the 'name' in your container_definitions
    container_port   = 8080                # Must match the 'containerPort' in your container_definitions
  }  

  force_new_deployment = true 
  
  deployment_controller {
    # type = "ECS"
    type = var.app_environment == "prod" ? "CODE_DEPLOY" : "ECS"
  }

  lifecycle {
    ignore_changes = [load_balancer, task_definition, desired_count]
  }

  # Optional: Enable service discovery, auto scaling, etc.
  tags = {
    Environment = "Development"
    Service     = "Safina"
  }
  

  # Ensure the service is created after the listener is ready
  depends_on = [
    data.aws_ecs_service.ecs_service,
    data.aws_lb_listener.safina_listener_https_443,
    # data.aws_lb_target_group.safina_ecs_tg,
    aws_lb_target_group.tg
  ]
}
