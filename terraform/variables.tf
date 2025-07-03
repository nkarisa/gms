variable "security_group_ids" {
  description = "Desired task count"
  type        = list(string)
#   default     = ["sg-0850a8407b905cc9e","sg-0e19f4f7cd34fd03e","sg-0b823459bcef5d710","sg-05fac8023c682f93c"]
}

variable "desired_count" {
  description = "Desired task count"
  type        = number
  default     = 4
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

variable "cluster_name" {
  description = "ECS Cluster"
  type        = string
#   default     = "safina-app-cluster" 
}

variable "container_name" {
  description = "Task Container Name"
  type        = string
#   default     = "safina-app" 
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

variable "gitlab_secret_arn" {
  description = "AWS Secret ARN"
  type        = string
}

# variable "database_host" {
#   type        = string
# }

# variable "database_password" {
#   type        = string
# }

# variable "logtail_token" {
#   type        = string
# }

# variable "base_url" {
#   type        = string
# }
