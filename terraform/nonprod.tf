# # Create the AWS ECS Service 
resource "aws_ecs_service" "new_ecs_service" {
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
    target_group_arn = data.aws_lb_target_group.safina_ecs_tg.arn
    container_name   = var.container_name # Must match the 'name' in your container_definitions
    container_port   = 8080                # Must match the 'containerPort' in your container_definitions
  }  

  force_new_deployment = true 
  
  deployment_controller {
    # type = "ECS"
    type = var.app_environment == "prod" ? "CODE_DEPLOY" : "ECS"
  }

  # deployment_configuration {
  #   strategy =  var.app_environment == "prod" ? "BLUE_GREEN" : "ROLLING"
  # }

  lifecycle {
    ignore_changes = [ desired_count]
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
    data.aws_lb_target_group.safina_ecs_tg,
    # data.aws_cloudwatch_log_group.safina_ecs_log_group 
  ]
}
