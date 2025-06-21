variable "service_name" {
  description = "The name of the ECS service to update."
  type        = string
  default     = "nginx-ecs-service"  # To be removed
}

variable "cluster_name" {
  description = "The name of the ECS cluster the service belongs to."
  type        = string
  default     = "safina-cluster" # To be removed
}

variable "image_name" {
  description = "The name of the ECS cluster the service belongs to."
  type        = string
  default     = "nginx:1.28.0" # To be removed
}

variable "ecs_task_definition_family" {
  description = "The name of the ECS cluster the service belongs to."
  type        = string
  default     = "safina-app-task-def"
}

variable "task_definition_arn" {
  description = "The ARN of the new task definition to deploy. Setting this will trigger a service update."
  type        = string
  default     =  "arn:aws:ecs:eu-west-1:234204504144:task-definition/safina-app-task-def:11" # null # To be removed
}

variable "aws_region" {
  description = "The AWS region to deploy resources into."
  type        = string
  default     = "eu-west-1" # Replace with your desired AWS region
}

variable "desired_count" {
  description = "The desired number of tasks to run. Setting this will update the service's desired count."
  type        = number
  default     = null
}

variable "deployment_circuit_breaker_enable" {
  description = "Whether to enable the deployment circuit breaker."
  type        = bool
  default     = false
}

variable "deployment_circuit_breaker_rollback" {
  description = "Whether to roll back on deployment failure when the circuit breaker is enabled."
  type        = bool
  default     = false
}

variable "deployment_controller_type" {
  description = "The deployment controller type. Valid values are CODE_DEPLOY, ECS, or EXTERNAL."
  type        = string
  default     = "ECS"
  validation {
    condition     = contains(["CODE_DEPLOY", "ECS", "EXTERNAL"], var.deployment_controller_type)
    error_message = "The deployment_controller_type must be one of CODE_DEPLOY, ECS, or EXTERNAL."
  }
}

variable "deployment_maximum_percent" {
  description = "The upper limit (as a percentage of the desired count) of the number of tasks that are allowed in the RUNNING or PENDING state in a service during a deployment."
  type        = number
  default     = null # Default is 200 by AWS if not specified
}

variable "deployment_minimum_healthy_percent" {
  description = "The lower limit (as a percentage of the desired count) of the number of running tasks that must remain running and healthy in a service during a deployment."
  type        = number
  default     = null # Default is 100 by AWS if not specified
}

variable "force_new_deployment" {
  description = "Forces a new deployment of the service. This can be useful when you've updated a task definition but the service isn't picking it up (e.g., due to an idempotent task definition change)."
  type        = bool
  default     = false
}