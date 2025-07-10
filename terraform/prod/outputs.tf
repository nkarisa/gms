# Output the ARN of the created ECS Task Definition
output "task_definition_arn" {
  description = "The Amazon Resource Name (ARN) of the Nginx ECS task definition."
  value       = aws_ecs_task_definition.task_definition.arn
}