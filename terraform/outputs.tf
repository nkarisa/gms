
# Output the ARN of the created ECS cluster
# output "ecs_cluster_arn" {
#   description = "The Amazon Resource Name (ARN) of the ECS cluster."
#   value       = aws_ecs_cluster.safina_app_cluster.arn
# }

# Output the name of the created ECS cluster
# output "ecs_cluster_name" {
#   description = "The name of the ECS cluster."
#   value       = aws_ecs_cluster.safina_app_cluster.name
# }

# Output the ARN of the created ECS Task Definition
output "task_definition_arn" {
  description = "The Amazon Resource Name (ARN) of the Nginx ECS task definition."
  value       = aws_ecs_task_definition.task_definition.arn
}

# Output the ARN of the ECS Task Execution Role
# output "ecs_task_execution_role_arn" {
#   description = "The Amazon Resource Name (ARN) of the ECS Task Execution Role."
#   value       = aws_iam_role.ecs_task_execution_role.arn
# }

# Output the ARN of the ECS Task Role with S3 Admin Access
# output "ecs_task_role_s3_admin_arn" {
#   description = "The Amazon Resource Name (ARN) of the ECS Task Role with S3 Admin Access."
#   value       = aws_iam_role.ecs_task_role_s3_admin.arn
# }

output "ecs_service_arn" {
  description = "The Amazon Resource Name (ARN) of the created ECS service."
  value       = aws_ecs_service.new_ecs_service.arn
}

# output "nginx_log_group_arn" {
#   description = "The Amazon Resource Name (ARN) of the CloudWatch Log Group for Nginx."
#   value       = aws_cloudwatch_log_group.safina_ecs_log_group.arn
# }