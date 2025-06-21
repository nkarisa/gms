

terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0" # Use a compatible AWS provider version
    }
  }
  required_version = ">= 1.0.0"

  backend "s3" {
    bucket         = "safina-terraform-state" # Replace with your S3 bucket name
    key            = "devint/terraform.tfstate" # Path within the bucket for your state file
    region         = "eu-west-1"                     # AWS region where your S3 bucket is located
    encrypt        = true                            # Enable server-side encryption for the state file
  }
}

provider "aws" {
  region = "eu-west-1"  # Replace with your desired AWS region
}

variable "target_group_name" {
  description = "Target Group"
  type        = string
  default    = "safina-ecs-tg"
}

variable "elb_name" {
  description = "ELB"
  type        = string
  default     = "Safina-sandbox-ELB"
}

variable "vpc_id" {
  description = "Task Definition"
  type        = string
  default     = "vpc-0f2a934dedb428d4f"
}

variable "task_definition_family" {
  description = "Task Definition"
  type        = string
  default     = "safina-app-task-def-devint"
}

variable "cluster_name" {
  description = "ECS Cluster"
  type        = string
  default     = "safina-app-cluster" 
}

variable "container_name" {
  description = "Task Container Name"
  type        = string
  default     = "safina-app" 
}

variable "image_name" {
  description = "Task Image"
  type        = string
  default     = "nginx:1.28"
}

variable "aws_region" {
  description = "AWS Region"
  type        = string
  default     = "eu-west-1"
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

# Data source for the VPC
data "aws_vpc" "selected_vpc" {
  id = var.vpc_id # Replace with your actual VPC ID
}

# Data source for private subnets within the specified VPC
data "aws_subnets" "selected_subnets" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.selected_vpc.id]
  }
  # Assuming private subnets are tagged with "kubernetes.io/role/internal-elb"
  # or you can use a custom tag like "Purpose" = "private"
  filter {
    name   = "tag:kubernetes.io/role/internal-elb"
    values = ["1"]
  }
  # Alternative if your private subnets use a custom tag:
  # filter {
  #   name   = "tag:Purpose"
  #   values = ["private"]
  # }
}


# Data source for the existing Application Load Balancer
data "aws_lb" "safina_elb" {
  name = var.elb_name # Replace with your actual ALB name
}

# Data source for the existing Listener (HTTP:443 on the ALB)
data "aws_lb_listener" "safina_listener_https_443" {
  load_balancer_arn = data.aws_lb.safina_elb.arn
  port              = 443
  # protocol          = "HTTPS" # Assuming 443 implies HTTPS
}

# Data source for the existing Target Group
data "aws_lb_target_group" "safina_ecs_tg" {
  name = var.target_group_name # Replace with your actual Target Group name
}

# Define the AWS ECS Cluster resource
resource "aws_ecs_cluster" "safina_app_cluster" {
  name = var.cluster_name # Give your cluster a meaningful name

  # Optional: You can add settings here, for example, to enable CloudWatch Container Insights
  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  tags = {
    Environment = "Development"
    Project     = "Safina App"
  }
}


# IAM Role for ECS Task Execution
resource "aws_iam_role" "ecs_task_execution_role" {
  name = "ecs-task-execution-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17",
    Statement = [
      {
        Action = "sts:AssumeRole",
        Effect = "Allow",
        Principal = {
          Service = "ecs-tasks.amazonaws.com"
        }
      }
    ]
  })

  tags = {
    Environment = "Development"
    Service     = "Safina"
  }
}

# Attach the managed policy for ECS Task Execution
resource "aws_iam_role_policy_attachment" "ecs_task_execution_policy" {
  role       = aws_iam_role.ecs_task_execution_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}


# IAM Role for the ECS Task (for the application inside the container) with S3 Admin access
resource "aws_iam_role" "ecs_task_role_s3_admin" {
  name = "ecs-task-role-s3-admin"

  assume_role_policy = jsonencode({
    Version = "2012-10-17",
    Statement = [
      {
        Action = "sts:AssumeRole",
        Effect = "Allow",
        Principal = {
          Service = "ecs-tasks.amazonaws.com"
        }
      }
    ]
  })

  tags = {
    Environment = "Development"
    Service     = "Safina"
    Access      = "S3FullAdmin"
  }
}



# Attach the AmazonS3FullAccess managed policy to the task role
resource "aws_iam_role_policy_attachment" "ecs_task_s3_admin_policy" {
  role       = aws_iam_role.ecs_task_role_s3_admin.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonS3FullAccess"
}



# Define the AWS ECS Task Definition for Nginx
resource "aws_ecs_task_definition" "task_definition" {
  family                   = var.task_definition_family # A logical name for your task definition
  cpu                      = "256"       # CPU units (e.g., 256 for 0.25 vCPU)
  memory                   = "512"       # Memory in MiB
  network_mode             = "awsvpc"    # Recommended network mode for Fargate
  requires_compatibilities = ["FARGATE"] # Or ["EC2"] if you're using EC2 launch type
  execution_role_arn       = aws_iam_role.ecs_task_execution_role.arn # Reference the new execution role
  task_role_arn            = aws_iam_role.ecs_task_role_s3_admin.arn  # Reference the new task role with S3 access
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

# Create the AWS ECS Service
# resource "aws_ecs_service" "_service" {
#   name            = "nginx-ecs-service"
#   cluster         = aws_ecs_cluster.my_ecs_cluster.id
#   task_definition = aws_ecs_task_definition.nginx_task_definition.arn
#   desired_count   = 2
#   launch_type     = "FARGATE"

#   network_configuration {
#     subnets         = data.aws_subnets.selected_subnets.ids
#     security_groups = ["sg-123456", "sg-7890123"] # Replace with your actual security group IDs
#     assign_public_ip = false
#   }

#   load_balancer {
#     target_group_arn = data.aws_lb_target_group.safina_ecs_tg.arn
#     container_name   = "nginx-container" # Must match the 'name' in your container_definitions
#     container_port   = 80                # Must match the 'containerPort' in your container_definitions
#   }

#   # Optional: Enable service discovery, auto scaling, etc.
#   tags = {
#     Environment = "Development"
#     Service     = "Nginx"
#   }

#   # Ensure the service is created after the listener is ready
#   depends_on = [
#     aws_iam_role_policy_attachment.ecs_task_execution_policy,
#     aws_iam_role_policy_attachment.ecs_task_s3_admin_policy,
#     data.aws_lb_listener.safina_listener_https_443,
#     data.aws_lb_target_group.safina_ecs_tg
#   ]
# }


# resource "aws_ecs_service" "update_service" {
#   name            = var.service_name
#   cluster         = data.aws_ecs_cluster.existing_cluster.id
#   # Reference the ARN of the newly created task definition revision.
#   task_definition = aws_ecs_task_definition.new_revision.arn

#   # Add this network_configuration block
#   network_configuration {
#     subnets         = var.subnet_ids
#     security_groups = var.security_group_ids
#     # This should typically be set to true for services running in awsvpc mode
#     assign_public_ip = false # Or false, depending on your network design (e.g., if you have a NAT Gateway)
#   }

# }


# Output the ARN of the created ECS cluster
output "ecs_cluster_arn" {
  description = "The Amazon Resource Name (ARN) of the ECS cluster."
  value       = aws_ecs_cluster.safina_app_cluster.arn
}

# Output the name of the created ECS cluster
output "ecs_cluster_name" {
  description = "The name of the ECS cluster."
  value       = aws_ecs_cluster.safina_app_cluster.name
}

# Output the ARN of the created ECS Task Definition
output "task_definition_arn" {
  description = "The Amazon Resource Name (ARN) of the Nginx ECS task definition."
  value       = aws_ecs_task_definition.task_definition.arn
}

# Output the ARN of the ECS Task Execution Role
output "ecs_task_execution_role_arn" {
  description = "The Amazon Resource Name (ARN) of the ECS Task Execution Role."
  value       = aws_iam_role.ecs_task_execution_role.arn
}

# Output the ARN of the ECS Task Role with S3 Admin Access
output "ecs_task_role_s3_admin_arn" {
  description = "The Amazon Resource Name (ARN) of the ECS Task Role with S3 Admin Access."
  value       = aws_iam_role.ecs_task_role_s3_admin.arn
}