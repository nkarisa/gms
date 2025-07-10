# # resource "aws_ecs_cluster" "main" {
# #   name = "my-ecs-cluster"
# # }

# # resource "aws_ecs_task_definition" "app" {
# #   family = "my-app-task"
# #   # ... (container definitions, network mode, etc.)
# # }

# # resource "aws_ecs_service" "app" {
# #   name = "my-app-service"
# #   cluster = data.aws_ecs_cluster.safina-cluster.id
# #   task_definition = aws_ecs_task_definition.app.arn
# #   # ... (desired_count, network configuration, load balancer details, etc.)
# # }

# resource "aws_codedeploy_app" "safina-deployment" {
#   compute_platform = "ECS"
#   name = "safina-deployment"
# }

# resource "aws_codedeploy_deployment_group" "safina-deployment-group" {
#   app_name = aws_codedeploy_app.safina-deployment.name
#   deployment_group_name = "safina-deployment-group"
#   service_role_arn = data.aws_iam_role.safina-codedeploy-role.arn
#   ecs_service {
#     cluster_name = data.aws_ecs_cluster.safina_app_cluster.cluster_name
#     service_name = data.aws_ecs_service.safina-app-service.service_name
#   }
#   deployment_style {
#     deployment_type = "BLUE_GREEN"
#     deployment_option = "WITH_TRAFFIC_CONTROL"
#   }

#   auto_rollback_configuration {
#     enabled = true
#     events  = ["DEPLOYMENT_FAILURE"]
#   }

#   blue_green_deployment_config {
#     deployment_ready_option {
#       action_on_timeout = "CONTINUE_DEPLOYMENT"
#     }

#     terminate_blue_instances_on_deployment_success {
#       action                           = "TERMINATE"
#       termination_wait_time_in_minutes = 5
#     }
#   }


#   load_balancer_info {
#     target_group_pair_info {
#       prod_traffic_route {
#         listener_arns = [aws_lb_listener.example.arn]
#       }

#       target_group {
#         name = aws_lb_target_group.blue.name
#       }

#       target_group {
#         name = aws_lb_target_group.green.name
#       }
#     }
#   }
# }
