resource "aws_ecs_service" "this" {
  # Required attributes to identify the service
  name        = var.service_name
  cluster     = var.cluster_name

  # Optional attributes to update
  # Only set if provided, otherwise Terraform will leave the existing value
  # or use the default from the AWS API if not explicitly managed.

  # Task Definition Update
  # This is the most common reason to update a service.
  # If you provide a new task_definition_arn, the service will perform a rolling update.
  # The `force_new_deployment` flag ensures a new deployment is triggered even if the task definition ARN hasn't technically changed (e.g., due to an in-place update of the task definition that doesn't generate a new ARN).
  # count = var.task_definition_arn != null || var.force_new_deployment ? 1 : 0
  task_definition = var.task_definition_arn
  force_new_deployment = var.force_new_deployment

  # Desired Count Update
  desired_count = var.desired_count # != null ? var.desired_count : null

  # Deployment Configuration Update
  deployment_circuit_breaker {
    enable   = var.deployment_circuit_breaker_enable
    rollback = var.deployment_circuit_breaker_rollback
  }

  deployment_controller {
    type = var.deployment_controller_type
  }

  deployment_maximum_percent        = var.deployment_maximum_percent != null ? var.deployment_maximum_percent : null
  deployment_minimum_healthy_percent = var.deployment_minimum_healthy_percent != null ? var.deployment_minimum_healthy_percent : null

  # Other attributes can be added here as needed for updates,
  # but be mindful that some attributes might trigger recreation
  # rather than an in-place update.
  # For example, changing the `launch_type` would likely recreate the service.
  # This module focuses on typical in-place updates.

  lifecycle {
    # It's generally a good idea to ignore changes to attributes that might be
    # managed by ECS itself (e.g., during auto-scaling or service discovery updates)
    # unless you explicitly want to control them.
    # For a service update module, we're explicitly trying to change things,
    # so being careful here is important.
    # If the module is specifically for *updating* then we expect these
    # to be set by the module.
    ignore_changes = [
      # Example: if you don't want the module to manage these after initial creation
      # tags,
      # tags_all,
    ]
  }
}

# Data source to retrieve existing service information if you need to reference
# attributes that are *not* being updated by the module, or to confirm the state.
# data "aws_ecs_service" "existing" {
#   service_name = var.service_name
#   cluster_arn  = aws_ecs_service.this.cluster
# }