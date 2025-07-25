locals {
  # appspec file  
  appspec = {
    version = "0.0"
    Resources = [
      {
        TargetService = {
          Type = "AWS::ECS::Service"
          Properties = {
            TaskDefinition = aws_ecs_task_definition.task_definition.arn
            LoadBalancerInfo = {
              ContainerName = var.container_name
              ContainerPort = var.container_port
            }
          }
        }
      }
    ]
  }
  appspec_content = replace(jsonencode(local.appspec), "\"", "\\\"")
  appspec_sha256  = sha256(jsonencode(local.appspec))

  # create deployment bash script
  script = <<EOF
    #!/bin/bash

    echo "creating deployment ..."
    ID=$(aws deploy create-deployment \
        --application-name ${var.codedeploy_application_name} \
        --deployment-group-name ${var.deployment_group_name} \
        --revision '{"revisionType": "AppSpecContent", "appSpecContent": {"content": "${local.appspec_content}", "sha256": "${local.appspec_sha256}"}}' \
        --output text \
        --query '[deploymentId]')

    echo "======================================================="
    echo "waiting for deployment $deploymentId to finish ..."
    STATUS=$(aws deploy get-deployment \
        --deployment-id $ID \
        --output text \
        --query '[deploymentInfo.status]')

    while [[ $STATUS == "Created" || $STATUS == "InProgress" || $STATUS == "Pending" || $STATUS == "Queued" || $STATUS == "Ready" ]]; do
        echo "Status: $STATUS..."
        STATUS=$(aws deploy get-deployment \
            --deployment-id $ID \
            --output text \
            --query '[deploymentInfo.status]')

        SLEEP_TIME=30

        echo "Sleeping for: $SLEEP_TIME Seconds"
        sleep $SLEEP_TIME
    done

    if [[ $STATUS == "Succeeded" ]]; then
        echo "Deployment succeeded."
    else
        echo "Deployment failed!"
        exit 1
    fi

EOF

}

resource "local_file" "deploy_script" {
  count = var.app_environment == "prod" ? 1 : 0

  filename             = "${path.module}/deploy_script.txt"
  directory_permission = "0755"
  file_permission      = "0644"
  content              = local.script

  depends_on = [ 
    aws_codedeploy_app.this,
    aws_codedeploy_deployment_group.this,
  ]
}

resource "null_resource" "start_deploy" {
  count = var.app_environment == "prod" ? 1 : 0

  triggers = {
    appspec_sha256 = local.appspec_sha256 # run only if appspec file changed
  }

  provisioner "local-exec" {
    command     = local.script
    interpreter = ["/bin/bash", "-c"]
  }

  depends_on = [ 
    aws_codedeploy_app.this,
    aws_codedeploy_deployment_group.this,
  ]
}


resource "aws_codedeploy_app" "safina-app-deploy" {
  compute_platform = "ECS"
  name             = var.codedeploy_application_name
}

resource "aws_codedeploy_deployment_group" "safina-app-deploy-group" {
  app_name               = var.codedeploy_application_name
  deployment_group_name  = var.deployment_group_name
  deployment_config_name = "CodeDeployDefault.ECSAllAtOnce"
  service_role_arn       = data.aws_iam_role.safina-code-deploy-role.arn

  blue_green_deployment_config {
    deployment_ready_option {
      action_on_timeout = "CONTINUE_DEPLOYMENT"
    }

    terminate_blue_instances_on_deployment_success {
      action                           = "TERMINATE"
      termination_wait_time_in_minutes = 1
    }
  }

  ecs_service {
    cluster_name = data.aws_ecs_cluster.safina_app_cluster.cluster_name
    service_name = data.aws_ecs_service.ecs_service.service_name
  }

  deployment_style {
    deployment_option = "WITH_TRAFFIC_CONTROL"
    deployment_type   = "BLUE_GREEN"
  }
  auto_rollback_configuration {
    enabled = true
    events  = ["DEPLOYMENT_FAILURE"] 
  }

  load_balancer_info {
    target_group_pair_info {
      prod_traffic_route {
        listener_arns = [data.aws_lb_listener.safina_listener_https_443.arn]
      }

      target_group {
        name = aws_lb_target_group.tg[0].name
      }

      target_group {
        name = aws_lb_target_group.tg[1].name
      }
    }
  }

  depends_on = [
    aws_codedeploy_app.safina-app-deploy
  ]
}


# data "aws_iam_policy_document" "assume_by_codedeploy" {
#   statement {
#     sid     = ""
#     effect  = "Allow"
#     actions = ["sts:AssumeRole"]

#     principals {
#       type        = "Service"
#       identifiers = ["codedeploy.amazonaws.com"]
#     }
#   }
# }

# resource "aws_iam_role" "codedeploy" {
#   name               = "codedeploy"
#   assume_role_policy = data.aws_iam_policy_document.assume_by_codedeploy.json
# }


# data "aws_iam_policy_document" "codedeploy" {
#   statement {
#     sid    = "AllowLoadBalancingAndECSModifications"
#     effect = "Allow"

#     actions = [
#       "ecs:CreateTaskSet",
#       "ecs:DeleteTaskSet",
#       "ecs:DescribeServices",
#       "ecs:UpdateServicePrimaryTaskSet",
#       "elasticloadbalancing:DescribeListeners",
#       "elasticloadbalancing:DescribeRules",
#       "elasticloadbalancing:DescribeTargetGroups",
#       "elasticloadbalancing:ModifyListener",
#       "elasticloadbalancing:ModifyRule",
#       "s3:GetObject"
#     ]

#     resources = ["*"]
#   }
#   statement {
#     sid    = "AllowPassRole"
#     effect = "Allow"

#     actions = ["iam:PassRole"]

#     resources = [
#       aws_iam_role.app_task_role.arn
#     ]
#   }

#   statement {
#     sid    = "DeployService"
#     effect = "Allow"

#     actions = ["ecs:DescribeServices",
#       "codedeploy:GetDeploymentGroup",
#       "codedeploy:CreateDeployment",
#       "codedeploy:GetDeployment",
#       "codedeploy:GetDeploymentConfig",
#     "codedeploy:RegisterApplicationRevision"]

#     resources = [
#       aws_ecs_service.new_ecs_service_prod.id,
#       aws_codedeploy_deployment_group.safina-app-deploy-group.arn,
#       "arn:aws:codedeploy:${var.aws_region}:${var.aws_account_id}:deploymentconfig:*}",
#       aws_codedeploy_app.safina-app-deploy.arn
#     ]
#   }


# }
# resource "aws_iam_role_policy" "codedeploy" {
#   role   = aws_iam_role.codedeploy.name
#   policy = data.aws_iam_policy_document.codedeploy.json
# }

