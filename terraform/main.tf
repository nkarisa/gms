# Define variables for your existing AWS ECS resources and new task definition details.
# You will need to replace the default values with your actual resource names and configurations.

variable "cluster_name" {
  description = "The name of your existing ECS cluster."
  type        = string
  # default     = "safina-cluster" # <<< REPLACE WITH YOUR CLUSTER NAME
}

variable "image_name" {
  description = "The name of your image."
  type        = string
  # default     = "safina-cluster" # <<< REPLACE WITH YOUR CLUSTER NAME
}

variable "container_name" { # Corrected typo: "conatiner_name" -> "container_name"
  description = "The name of task the container."
  type        = string
  default     = "app"
}


variable "aws_region" {
  description = "The name of your image."
  type        = string
  default     = "eu-west-1" # <<< REPLACE WITH YOUR CLUSTER NAME
}


variable "task_definition_family" {
  description = "The family name of your existing ECS task definition."
  type        = string
  # default     = "safina-app-task-def" # <<< REPLACE WITH YOUR TASK DEFINITION FAMILY
}

variable "service_name" {
  description = "The name of your existing ECS service."
  type        = string
  # default     = "safina-devint-service" # <<< REPLACE WITH YOUR SERVICE NAME
}


# Add these variables if you don't have them already
variable "subnet_ids" {
  description = "A list of subnet IDs for the ECS service."
  type        = list(string)
  # Provide your actual subnet IDs here
}

variable "security_group_ids" {
  description = "A list of security group IDs for the ECS service."
  type        = list(string)
  # Provide your actual security group IDs here
}


# --- Data Source to get an existing ECS Cluster ---
data "aws_ecs_cluster" "existing_cluster" {
  cluster_name = var.cluster_name 
}

# --- Data Source to get the existing ECS Service ---
data "aws_ecs_service" "existing_service" {
  service_name = var.service_name # Replace with your actual ECS service name
  cluster_arn  = data.aws_ecs_cluster.existing_cluster.arn
}

# Define local values for dynamic configurations
locals {
  container_definitions = [
    {
      name        = var.container_name
      image       = var.image_name # This is now valid!
      cpu         = 256
      memory      = 512
      essential   = true
      portMappings = [
        {
          containerPort = 80
          hostPort      = 80
        }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = "/ecs/safina-app-task"
          "awslogs-region"        = var.aws_region # This is now valid!
          "awslogs-stream-prefix" = "ecs"
        }
      }
    }
  ]
}

# Data source to fetch the existing task definition details.
# This is useful to retrieve properties like 'network_mode', 'requires_compatibilities', 'cpu', 'memory',
# and 'execution_role_arn' from the current active revision, ensuring the new revision
# inherits these properties unless explicitly overridden.
data "aws_ecs_task_definition" "existing" {
  # We use the family and a regex to ensure we get the latest active revision for the family.
  # Terraform will automatically pick the highest revision number.
  task_definition = var.task_definition_family
}


# Resource to create a new revision of the ECS Task Definition.
# By providing the 'family' that already exists, ECS will create a new revision number.
resource "aws_ecs_task_definition" "new_revision" {
  family                   = var.task_definition_family
  # Apply jsonencode here, referencing the new local value.
  container_definitions    = jsonencode(local.container_definitions)
  # Inherit common properties from the existing task definition.
  # Adjust these if you intend to change them for the new revision.
  network_mode             = data.aws_ecs_task_definition.existing.network_mode
  requires_compatibilities = data.aws_ecs_task_definition.existing.requires_compatibilities
  cpu                      = data.aws_ecs_task_definition.existing.cpu
  memory                   = data.aws_ecs_task_definition.existing.memory
  execution_role_arn       = data.aws_ecs_task_definition.existing.execution_role_arn
  task_role_arn            = data.aws_ecs_task_definition.existing.task_role_arn # Optional: if your task needs an IAM role

  tags = {
    Name = "${var.task_definition_family}-new-revision"
  }
}

# Resource to update the existing ECS Service to use the new task definition revision.
# Terraform will detect that the 'task_definition' attribute has changed and perform an in-place update
# of the ECS service, initiating a new deployment with the new task definition revision.
resource "aws_ecs_service" "update_service" {
  name            = var.service_name
  cluster         = data.aws_ecs_cluster.existing_cluster.id
  # Reference the ARN of the newly created task definition revision.
  task_definition = aws_ecs_task_definition.new_revision.arn

  # Add this network_configuration block
  network_configuration {
    subnets         = var.subnet_ids
    security_groups = var.security_group_ids
    # This should typically be set to true for services running in awsvpc mode
    assign_public_ip = false # Or false, depending on your network design (e.g., if you have a NAT Gateway)
  }

}

# Data source to fetch existing service details if needed for copying other properties.
# data "aws_ecs_service" "existing" {
#   cluster_arn = "arn:aws:ecs:us-east-1:123456789012:cluster/${var.cluster_name}" # Replace with your cluster ARN
#   service_name = var.service_name
# }

# Output the ARN of the new task definition revision for verification.
output "new_task_definition_arn" {
  description = "The ARN of the new ECS task definition revision."
  value       = aws_ecs_task_definition.new_revision.arn
}

# Output the name of the updated ECS service.
output "updated_service_name" {
  description = "The name of the updated ECS service."
  value       = aws_ecs_service.update_service.name
}