terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0" # Use a compatible AWS provider version
    }
  }
  required_version = ">= 1.0.0"

  backend "s3" {
    bucket         = "" # "runner-cache-africanonprod" # runner-cache-africanonprod # Replace with your S3 bucket name
    key            = "" # "ciorg/regional/africa-developers/safinav3/prod/terraform.tfstate" # ciorg/regional/africa-developers/safinav3/devint/terraform.tfstate  # Path within the bucket for your state file
    region         = "eu-west-1"                     # AWS region where your S3 bucket is located
    encrypt        = true                            # Enable server-side encryption for the state file
  }
}

provider "aws" {
  region = local.aws_region  # Replace with your desired AWS region
}

