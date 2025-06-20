# terraform {
#     backend "http" {

#     }
# }

# provider "aws" {
#     region = "eu-west-1"
# }

# data "aws_caller_identity" "my_identity" {

# }

# --- VPC and Networking (Optional: if you want Terraform to create a new VPC) ---
resource "aws_vpc" "main" {
  count = var.vpc_id == "" ? 1 : 0 # Create VPC if vpc_id is not provided
  cidr_block = "10.0.0.0/16"
  enable_dns_hostnames = true
  enable_dns_support   = true

  tags = {
    Name = "${var.project_name}-vpc"
  }
}

resource "aws_subnet" "public" {
  count = var.vpc_id == "" ? 2 : 0 # Create 2 public subnets if VPC is created by Terraform
  vpc_id            = aws_vpc.main[0].id
  cidr_block        = cidrsubnet(aws_vpc.main[0].cidr_block, 8, count.index)
  map_public_ip_on_launch = true
  availability_zone = data.aws_availability_zones.available.names[count.index]

  tags = {
    Name = "${var.project_name}-public-subnet-${count.index}"
  }
}

resource "aws_subnet" "private" {
  count = var.vpc_id == "" ? 2 : 0 # Create 2 private subnets if VPC is created by Terraform
  vpc_id            = aws_vpc.main[0].id
  cidr_block        = cidrsubnet(aws_vpc.main[0].cidr_block, 8, count.index + 2) # Offset for private subnets
  availability_zone = data.aws_availability_zones.available.names[count.index]

  tags = {
    Name = "${var.project_name}-private-subnet-${count.index}"
  }
}

resource "aws_internet_gateway" "main" {
  count = var.vpc_id == "" ? 1 : 0
  vpc_id = aws_vpc.main[0].id

  tags = {
    Name = "${var.project_name}-igw"
  }
}

resource "aws_route_table" "public" {
  count = var.vpc_id == "" ? 1 : 0
  vpc_id = aws_vpc.main[0].id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.main[0].id
  }

  tags = {
    Name = "${var.project_name}-public-rt"
  }
}

resource "aws_route_table_association" "public" {
  count = var.vpc_id == "" ? length(aws_subnet.public) : 0
  subnet_id      = aws_subnet.public[count.index].id
  route_table_id = aws_route_table.public[0].id
}

resource "aws_eip" "nat" {
  count = var.vpc_id == "" ? 2 : 0 # One NAT Gateway per AZ
  vpc = true
  tags = {
    Name = "${var.project_name}-nat-eip-${count.index}"
  }
}

resource "aws_nat_gateway" "main" {
  count = var.vpc_id == "" ? 2 : 0
  allocation_id = aws_eip.nat[count.index].id
  subnet_id     = aws_subnet.public[count.index].id

  tags = {
    Name = "${var.project_name}-nat-gw-${count.index}"
  }
  depends_on = [aws_internet_gateway.main]
}

resource "aws_route_table" "private" {
  count = var.vpc_id == "" ? 2 : 0 # One private route table per AZ for NAT
  vpc_id = aws_vpc.main[0].id

  route {
    cidr_block = "0.0.0.0/0"
    nat_gateway_id = aws_nat_gateway.main[count.index].id
  }

  tags = {
    Name = "${var.project_name}-private-rt-${count.index}"
  }
}

resource "aws_route_table_association" "private" {
  count = var.vpc_id == "" ? length(aws_subnet.private) : 0
  subnet_id      = aws_subnet.private[count.index].id
  route_table_id = aws_route_table.private[count.index].id
}

data "aws_availability_zones" "available" {
  state = "available"
}

locals {
  selected_vpc_id           = var.vpc_id != "" ? var.vpc_id : aws_vpc.main[0].id
  selected_private_subnets  = var.vpc_id != "" ? var.private_subnet_ids : [for s in aws_subnet.private : s.id]
  selected_public_subnets   = var.vpc_id != "" ? var.public_subnet_ids : [for s in aws_subnet.public : s.id]
}


# --- IAM Roles for ECS ---
resource "aws_iam_role" "ecs_task_execution_role" {
  name = "${var.project_name}-ecs-task-execution-role"

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
    Name = "${var.project_name}-ecs-execution-role"
  }
}

resource "aws_iam_role_policy_attachment" "ecs_task_execution_role_policy" {
  role       = aws_iam_role.ecs_task_execution_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

# Allow ECS to read secrets from Secrets Manager for private registry authentication
resource "aws_iam_policy" "ecs_secrets_manager_access" {
  name        = "${var.project_name}-ecs-secrets-manager-access"
  description = "Allows ECS task execution role to access secrets for private registry authentication"

  policy = jsonencode({
    Version = "2012-10-17",
    Statement = [
      {
        Effect = "Allow",
        Action = [
          "secretsmanager:GetSecretValue",
          "kms:Decrypt" # If your secret is KMS encrypted
        ],
        Resource = "*" # Restrict this to your specific secret ARN in production
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "ecs_secrets_manager_access_attach" {
  role       = aws_iam_role.ecs_task_execution_role.name
  policy_arn = aws_iam_policy.ecs_secrets_manager_access.arn
}

resource "aws_iam_role" "ecs_task_role" {
  name = "${var.project_name}-ecs-task-role"

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
    Name = "${var.project_name}-ecs-task-role"
  }
}

# --- AWS Secrets Manager for GitLab Registry Credentials ---
resource "aws_secretsmanager_secret" "gitlab_registry_credentials" {
  name        = "${var.project_name}-gitlab-registry-credentials"
  description = "Credentials for GitLab Container Registry"

  # Store username and password as a JSON string
  secret_string = jsonencode({
    username = var.gitlab_registry_username
    password = var.gitlab_registry_password
  })

  tags = {
    Name = "${var.project_name}-gitlab-registry-secret"
  }
}


# --- ECS Cluster ---
resource "aws_ecs_cluster" "main" {
  name = "${var.project_name}-cluster"

  tags = {
    Name = "${var.project_name}-cluster"
  }
}

# --- ECS Task Definition ---
resource "aws_ecs_task_definition" "app" {
  family                   = "${var.project_name}-task-definition"
  cpu                      = var.ecs_fargate_cpu
  memory                   = var.ecs_fargate_memory
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  execution_role_arn       = aws_iam_role.ecs_task_execution_role.arn
  task_role_arn            = aws_iam_role.ecs_task_role.arn

  container_definitions = jsonencode([
    {
      name        = var.project_name
      image       = var.gitlab_image_name
      cpu         = var.ecs_fargate_cpu
      memory      = var.ecs_fargate_memory
      essential   = true
      portMappings = [
        {
          containerPort = var.container_port
          hostPort      = var.container_port
          protocol      = "tcp"
        }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.app.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "ecs"
        }
      }
      # This is crucial for private registry authentication
      repositoryCredentials = {
        credentialsParameter = aws_secretsmanager_secret.gitlab_registry_credentials.arn
      }
    }
  ])

  tags = {
    Name = "${var.project_name}-task-definition"
  }
}

# --- CloudWatch Log Group for ECS Task Logs ---
resource "aws_cloudwatch_log_group" "app" {
  name              = "/ecs/${var.project_name}"
  retention_in_days = 7 # Adjust as needed

  tags = {
    Name = "${var.project_name}-log-group"
  }
}

# --- Security Group for ECS Tasks ---
resource "aws_security_group" "ecs_tasks_sg" {
  name        = "${var.project_name}-ecs-tasks-sg"
  description = "Allow inbound traffic to ECS tasks"
  vpc_id      = local.selected_vpc_id

  ingress {
    from_port   = var.container_port
    to_port     = var.container_port
    protocol    = "tcp"
    # Allow traffic from ALB or other services in the VPC
    security_groups = var.create_load_balancer ? [aws_security_group.alb_sg[0].id] : [aws_security_group.ecs_tasks_sg.id] # If no ALB, tasks can talk to themselves (for testing) or adjust
    description = "Allow inbound from ALB"
  }
  ingress {
    from_port = 0
    to_port   = 0
    protocol  = "-1" # All protocols
    self      = true # Allow communication within the security group
    description = "Allow self-referencing traffic"
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1" # Allow all outbound traffic
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "${var.project_name}-ecs-tasks-sg"
  }
}

# --- Application Load Balancer (ALB) (Optional) ---
resource "aws_lb" "app_alb" {
  count            = var.create_load_balancer ? 1 : 0
  name             = "${var.project_name}-alb"
  internal         = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb_sg[0].id]
  subnets          = local.selected_public_subnets

  tags = {
    Name = "${var.project_name}-alb"
  }
}

resource "aws_security_group" "alb_sg" {
  count = var.create_load_balancer ? 1 : 0
  name        = "${var.project_name}-alb-sg"
  description = "Allow HTTP/HTTPS traffic to ALB"
  vpc_id      = local.selected_vpc_id

  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "Allow HTTP from anywhere"
  }

  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "Allow HTTPS from anywhere"
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "${var.project_name}-alb-sg"
  }
}

resource "aws_lb_target_group" "app_tg" {
  count    = var.create_load_balancer ? 1 : 0
  name     = "${var.project_name}-tg"
  port     = var.container_port
  protocol = "HTTP"
  vpc_id   = local.selected_vpc_id

  health_check {
    path = "/" # Adjust to your application's health check endpoint
    protocol = "HTTP"
    matcher  = "200"
  }

  tags = {
    Name = "${var.project_name}-tg"
  }
}

resource "aws_lb_listener" "http" {
  count = var.create_load_balancer ? 1 : 0
  load_balancer_arn = aws_lb.app_alb[0].arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.app_tg[0].arn
  }
}

# --- ECS Service ---
resource "aws_ecs_service" "app" {
  name            = "${var.project_name}-service"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.app.arn
  desired_count   = var.desired_count
  launch_type     = "FARGATE"

  network_configuration {
    subnets         = local.selected_private_subnets
    security_groups = [aws_security_group.ecs_tasks_sg.id]
    assign_public_ip = false # Fargate tasks in private subnets generally don't need public IPs
  }

  dynamic "load_balancer" {
    for_each = var.create_load_balancer ? [1] : []
    content {
      target_group_arn = aws_lb_target_group.app_tg[0].arn
      container_name   = var.project_name
      container_port   = var.container_port
    }
  }

  # Optional: Deployment circuit breaker for automatic rollback
  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

  # Optional: Deployment controller for blue/green or rolling updates
  deployment_controller {
    type = "ECS"
  }

  tags = {
    Name = "${var.project_name}-service"
  }

  # Ensure the ALB and its listener are created before the ECS service
  depends_on = [
    aws_lb_listener.http,
    aws_lb.app_alb
  ]
}