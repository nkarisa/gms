output "ecs_cluster_name" {
  description = "The name of the ECS cluster."
  value       = aws_ecs_cluster.main.name
}

output "ecs_service_name" {
  description = "The name of the ECS service."
  value       = aws_ecs_service.app.name
}

output "alb_dns_name" {
  description = "The DNS name of the Application Load Balancer."
  value       = var.create_load_balancer ? aws_lb.app_alb[0].dns_name : "ALB not created"
}

output "gitlab_registry_secret_arn" {
  description = "The ARN of the Secrets Manager secret storing GitLab registry credentials."
  value       = aws_secretsmanager_secret.gitlab_registry_credentials.arn
}