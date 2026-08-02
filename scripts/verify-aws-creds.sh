#!/usr/bin/env bash
# Simple verification script to run after `aws configure` with the new IAM user
set -euo pipefail

echo "Checking AWS CLI version..."
aws --version

echo "Checking STS identity (caller)..."
aws sts get-caller-identity --output json

echo "Checking Ansible and installed collections..."
ansible --version || true
ansible-galaxy collection list | grep -E "amazon.aws|community.aws" || true

echo "Checking eksctl and kubectl..."
eksctl version || true
kubectl version --client --short || true

echo "All checks executed. If the commands above returned sensible outputs, the credentials and tools look OK."