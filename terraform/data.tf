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

# Define local values for dynamic configurations
locals {
  aws_region = var.aws_region

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
