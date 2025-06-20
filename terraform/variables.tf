variable "project_name" {
  description = "A unique name for your project, used for resource naming."
  type        = string
  default     = "my-gitlab-ecs-app"
}

variable "aws_region" {
  description = "The AWS region to deploy resources into."
  type        = string
  default     = "us-east-1" # Replace with your desired AWS region
}

variable "gitlab_image_name" {
  description = "The full path to your container image in GitLab Container Registry (e.g., registry.gitlab.com/user/repo/image:tag)"
  type        = string
}

variable "container_port" {
  description = "The port your application container exposes."
  type        = number
  default     = 80
}

variable "desired_count" {
  description = "The desired number of tasks to run in the ECS service."
  type        = number
  default     = 1
}

variable "ecs_fargate_cpu" {
  description = "The CPU units for the Fargate task (e.g., 256, 512, 1024, 2048, 4096)."
  type        = number
  default     = 256
}

variable "ecs_fargate_memory" {
  description = "The memory (in MiB) for the Fargate task (e.g., 512, 1024, 2048, 4096, 8192)."
  type        = number
  default     = 512
}

variable "vpc_id" {
  description = "The ID of an existing VPC to deploy into. If empty, a new VPC will be created."
  type        = string
  default     = ""
}

variable "private_subnet_ids" {
  description = "A list of private subnet IDs where ECS tasks will run. Required if vpc_id is provided."
  type        = list(string)
  default     = []
}

variable "public_subnet_ids" {
  description = "A list of public subnet IDs for the ALB. Required if vpc_id is provided and you want an ALB."
  type        = list(string)
  default     = []
}

variable "create_load_balancer" {
  description = "Set to true to create an Application Load Balancer (ALB) for the service."
  type        = bool
  default     = true
}

variable "gitlab_registry_username" {
  description = "Username for GitLab Container Registry (typically 'gitlab-ci-token' for CI/CD)."
  type        = string
  sensitive   = true
}

variable "gitlab_registry_password" {
  description = "Password/token for GitLab Container Registry (e.g., your deploy token or CI_JOB_TOKEN)."
  type        = string
  sensitive   = true
}