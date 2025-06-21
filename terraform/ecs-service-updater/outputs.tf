# output "service_arn" {
#   description = "The ARN of the updated ECS service."
#   value       = aws_ecs_service.this[0].arn
# }

# output "service_name" {
#   description = "The name of the updated ECS service."
#   value       = aws_ecs_service.this[0].name
# }

# output "updated_task_definition_arn" {
#   description = "The ARN of the task definition currently deployed by the service after the update."
#   value       = aws_ecs_service.this[0].task_definition
# }

# output "updated_desired_count" {
#   description = "The desired count of the service after the update."
#   value       = aws_ecs_service.this[0].desired_count
# }