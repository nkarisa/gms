terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0" # Use a compatible AWS provider version
    }
  }
  required_version = ">= 1.0.0"
}

provider "aws" {
  region = "eu-west-1" # Replace with your desired AWS region
}