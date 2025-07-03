data "aws_efs_file_system" "safina-ecs-tasks-efs" {
  file_system_id = "fs-0093c43d73ea36291" # Replace with your EXISTING EFS File System ID
}

data "aws_ecs_cluster" "safina_app_cluster" {
  cluster_name = "safina-cluster"
  # arn = "arn:aws:ecs:eu-west-1:234204504144:cluster/safina-cluster"
}

data "aws_iam_role" "ecs_task_role_s3_admin" {
  name = "ecs_task_role_s3_admin"
}

data "aws_ecs_service" "ecs_service" {
  service_name = "safina-app-service-devint"
  cluster_arn  = data.aws_ecs_cluster.safina_app_cluster.arn
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

data "aws_iam_role" "ecs_task_execution_role" {
  name = "ecsTaskExecutionRole"
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

# Define local values for dynamic configurations
locals {
  aws_region = var.aws_region

  container_definitions = [
    {
      name        = var.container_name
      image       = var.image_name # This should be your full GitLab/ECR image path, e.g., "registry.gitlab.com/ciorg/regional/africa-developers/safinav3:latest"
      cpu         = 256
      memory      = 512
      essential   = true
      portMappings = [
        {
          containerPort = 8080
          hostPort      = 8080
        }
      ]

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.safina_ecs_log_group.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "ecs"
        }
      }

      mountPoints = [
        {
          sourceVolume  = "safina-ecs-volume",
          containerPath = "/var/www/html", # The path inside your container where EFS will be mounted
          readOnly      = false
        }
      ]
     
      # repositoryCredentials = {
      #   credentialsParameter = var.gitlab_secret_arn # Reference the variable you'll define for the Secret ARN
      # }
    }
  ]
}