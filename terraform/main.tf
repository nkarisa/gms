# Define the AWS ECS Task Definition for Safina
resource "aws_ecs_task_definition" "task_definition" {
  family                   = var.task_definition_family # A logical name for your task definition
  cpu                      = var.task_cpu       # CPU units (e.g., 256 for 0.25 vCPU)
  memory                   = var.task_memory       # Memory in MiB
  network_mode             = "awsvpc"    # Recommended network mode for Fargate
  requires_compatibilities = ["FARGATE"] # Or ["EC2"] if you're using EC2 launch type
  execution_role_arn       =   data.aws_iam_role.ecs_task_execution_role.arn # Reference the new execution role # "arn:aws:iam::234204504144:role/ecsTaskExecutionRole"
  task_role_arn            = data.aws_iam_role.ecs_task_role_s3_admin.arn  # Reference the new task role with S3 access

  skip_destroy = true

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


