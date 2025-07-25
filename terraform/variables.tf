variable "codedeploy_application_name" {
  description = "Code Deploy Application Name"
  type        = string
  default     = "safina-app-deploy"
}

variable "deployment_group_name" {
  description = "Code Deploy Deployment Group Name"
  type        = string
  default     = "safina-app-deploy-group"
}

variable "app_name" {
  description = "Application Name"
  type        = string
  default     = "ssafina-app"
}

variable "SESSION_HANDLER" {
  description = "CI session handler" # Redis, DynamoDb
  type        = string
}

variable "SESSION_DYNAMODB_TABLE" {
  description = "Session DynamoDb Table"
  type        = string
}

variable "REDIS_SERVER" {
  description = "Redis Server"
  type        = string 
}

variable "REDIS_PORT" {
  description = "Redis Server Port" 
  type        = string
  default     = "6379"
}

variable "logtail_token" {
  description = "Logtail Token"
  type        = string 
}

variable "base_url" {
  description = "Base URL"
  type        = string 
}

variable "db_host" {
  description = "Database Host"
  type        = string 
}

variable "db_pass" {
  description = "Database Password"
  type        = string 
}

variable "db_user" {
  description = "Database User name"
  type        = string 
}

variable "db_name" {
  description = "Database Name"
  type        = string 
}

variable "tag" {
  description = "Gitlab commit SHA"
  type        = string 
}

variable "app_environment" {
  description = "Application environment"
  type        = string 
  default     = "devint" 
}

variable "security_group_ids" {
  description = "Desired task count"
  type        = list(string)
#   default     = ["sg-0850a8407b905cc9e","sg-0e19f4f7cd34fd03e","sg-0b823459bcef5d710","sg-05fac8023c682f93c"]
}

variable "desired_count" {
  description = "Desired task count"
  type        = number
  default     = 2
}

variable "service_name" {
  description = "Service Name"
  type        = string
#   default     = "safina-ecs-service-devint"
}

variable "target_group_name" {
  description = "Target Group"
  type        = string
  default    = "safina-ecs-tg"
}

variable "elb_name" {
  description = "ELB"
  type        = string
#   default     = "Safina-sandbox-ELB"
}

variable "vpc_id" {
  description = "Task Definition"
  type        = string
#   default     = "vpc-0f2a934dedb428d4f"
}

variable "task_definition_family" {
  description = "Task Definition"
  type        = string
#   default     = "safina-app-task-def-devint"
}

variable "container_name" {
  description = "Task Container Name"
  type        = string
#   default     = "safina-app" 
}

variable "container_port" {
  description = "Task Container Port"
  type        = string
  default     = "8080" 
}

variable "image_name" {
  description = "Task Image"
  type        = string
#   default     = "nginx:1.28"
}

variable "aws_region" {
  description = "AWS Region"
  type        = string
  default     = "eu-west-1"
}

variable "aws_account_id" {
  description = "AWS Account Id"
  type        = string
}

variable "task_cpu" {
  description = "The number of CPU units reserved for the task."
  type        = number
  default     = 512 # Increased to accommodate two containers (0.5 vCPU)
}

variable "task_memory" {
  description = "The amount of memory (in MiB) reserved for the task."
  type        = number
  default     = 1024 # Increased to accommodate two containers (1 GB)
}