# --- CloudWatch Log Group for ECS Service ---
resource "aws_cloudwatch_log_group" "safina_ecs_log_group" {
  name              = "/ecs/safina-app" # This must match awslogs-group in task definition
  retention_in_days = 7                # Retain logs for 7 days (adjust as needed)

  tags = {
    Environment = "Development"
    Service     = "Safina App"
  }
}

# Define the AWS ECS Task Definition for Safina
resource "aws_ecs_task_definition" "task_definition" {
  family                   = var.task_definition_family # A logical name for your task definition
  cpu                      = var.task_cpu       # CPU units (e.g., 256 for 0.25 vCPU)
  memory                   = var.task_memory       # Memory in MiB
  network_mode             = "awsvpc"    # Recommended network mode for Fargate
  requires_compatibilities = ["FARGATE"] # Or ["EC2"] if you're using EC2 launch type
  execution_role_arn       =   data.aws_iam_role.ecs_task_execution_role.arn # Reference the new execution role # "arn:aws:iam::234204504144:role/ecsTaskExecutionRole"
  task_role_arn            = data.aws_iam_role.ecs_task_role_s3_admin.arn  # Reference the new task role with S3 access

  runtime_platform {
    operating_system_family = "LINUX"
    cpu_architecture        = "X86_64"
  }
  
  # Container definitions in JSON format
  container_definitions = jsonencode(local.container_definitions)

  tags = {
    Name = "${var.task_definition_family}"
  }
}

# # Create the AWS ECS Service 
resource "aws_ecs_service" "new_ecs_service" {
  name            = var.service_name
  cluster         = data.aws_ecs_cluster.safina_app_cluster.id
  # task_definition = aws_ecs_task_definition.task_definition.arn
  desired_count   = var.desired_count
  launch_type     = "FARGATE"
  deployment_minimum_healthy_percent = 50
  deployment_maximum_percent = 100

  # To be removed later. This is just present during development work.
  enable_execute_command = true

  # This is the key argument for waiting for stability
  # wait_for_steady_state = true 

  network_configuration {
    subnets         = data.aws_subnets.selected_subnets.ids
    security_groups = var.security_group_ids
    # This should typically be set to true for services running in awsvpc mode
    assign_public_ip = false # Or false, depending on your network design (e.g., if you have a NAT Gateway)
  }

  # load_balancer {
  #   target_group_arn = data.aws_lb_target_group.safina_ecs_tg.arn
  #   container_name   = var.container_name # Must match the 'name' in your container_definitions
  #   container_port   = 8080                # Must match the 'containerPort' in your container_definitions
  # }  

  force_new_deployment = false 
  
  deployment_controller {
    type = "EXTERNAL"
  }

  # lifecycle {
  #   # This prevents Terraform from trying to recreate the service
  #   # if only 'triggers' changes. It just forces an update.
  #   ignore_changes = [
  #     triggers,
  #   ]
  # }

  # triggers = {
  #   # This ensures a deployment on every `terraform apply`
  #   redeploy_timestamp = timestamp()
  # }

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
    aws_cloudwatch_log_group.safina_ecs_log_group 
  ]
}
