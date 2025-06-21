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

variable "conatiner_name" {
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

variable "container_definitions_json" {
  description = "A list of maps defining your updated container definitions. This will create a new task definition revision."
  # Changed type to 'any' to allow direct list of maps in default,
  # and will apply jsonencode where it's used.
  type        = any
  # <<< IMPORTANT: REPLACE THE EXAMPLE BELOW WITH YOUR ACTUAL, UPDATED CONTAINER DEFINITIONS
  # Ensure this structure is valid and includes all necessary fields (e.g., 'name', 'image', 'portMappings', 'cpu', 'memory').
  # A common update is changing the 'image' tag to deploy a new application version.
  default = [
    {
      name        = var.container_name
      image       = var.image_name # <<< UPDATE THIS TO YOUR NEW IMAGE VERSION
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
          "awslogs-region"        = "eu-west-1" # <<< REPLACE WITH YOUR AWS REGION
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
  # Apply jsonencode here, as 'container_definitions' expects a JSON string.
  container_definitions    = jsonencode(var.container_definitions_json)
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
  cluster         = var.cluster_name
  # Reference the ARN of the newly created task definition revision.
  task_definition = aws_ecs_task_definition.new_revision.arn

  # By default, Terraform will manage the service as a whole.
  # If you want to prevent Terraform from managing other aspects of the service
  # that might be managed manually or by other tools, you might need to
  # use 'lifecycle { ignore_changes = [...] }'. However, for a standard update,
  # it's usually fine to let Terraform manage.

  # Important: Do not set 'desired_count' here if you want ECS service auto-scaling to manage it.
  # If 'desired_count' is set, Terraform will try to enforce that count,
  # potentially conflicting with auto-scaling policies.
  # You can fetch it from existing service data source if needed for consistency.
  # For this example, we assume `desired_count` is managed outside Terraform or
  # you are intentionally setting it here for the update.
  # desired_count   = data.aws_ecs_service.existing.desired_count # Example if you want to explicitly keep current count

  # You can fetch existing service details if you need to copy other configurations
  # such as load balancers, deployment circuit breakers, etc.
  # For simplicity, this example only updates the task definition.
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
