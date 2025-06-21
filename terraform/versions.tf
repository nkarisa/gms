

terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0" # Use a compatible AWS provider version
    }
  }
  required_version = ">= 1.0.0"

  backend "s3" {
    bucket         = "safina-terraform-state" # Replace with your S3 bucket name
    key            = "devint/terraform.tfstate" # Path within the bucket for your state file
    region         = var.aws_region                     # AWS region where your S3 bucket is located
    encrypt        = true                            # Enable server-side encryption for the state file
  }
}

provider "aws" {
  region = var.aws_region  # Replace with your desired AWS region
}