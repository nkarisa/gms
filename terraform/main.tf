# provider "aws" {
#   region = var.aws_region 
# }

# Assume you have an existing ECS cluster and service
# For demonstration purposes, let's pretend these exist:
# resource "aws_ecs_cluster" "example" {
#   name = "my-example-cluster"
# }

resource "aws_ecs_task_definition" "task_definition_family" {
  family                   = var.ecs_task_definition_family
  container_definitions    = jsonencode([
    {
      name      = var.service_name
      image     = var.image_name # Your new image version
      cpu       = 256
      memory    = 512
      essential = true
      portMappings = [
        {
          containerPort = 80
          hostPort      = 80
        }
      ]
    }
  ])
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = "256"
  memory                   = "512"
  execution_role_arn       = var.ecs_task_execution_role_arn # Replace with your role ARN
}

# Use the module to update the existing service
module "update_ecs_service" {
  source = "./ecs-service-updater" # Path to your module

  service_name        = var.service_name
  cluster_name       = var.cluster_name # aws_ecs_cluster.example.name
  task_definition_arn = aws_ecs_task_definition.task_definition_family.arn # Update to the new task definition
  desired_count       = 3                                      # Change desired count to 3
  force_new_deployment = true # Ensure a new deployment is triggered
  deployment_circuit_breaker_enable = true
  deployment_circuit_breaker_rollback = true
  deployment_maximum_percent = 200
  deployment_minimum_healthy_percent = 50
}

# output "updated_service_arn" {
#   value = module.update_ecs_service.service_arn
# }

# output "updated_service_desired_count" {
#   value = module.update_ecs_service.updated_desired_count
# }