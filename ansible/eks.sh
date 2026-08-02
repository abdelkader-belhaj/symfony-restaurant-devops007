#!/bin/bash
# Usage: ./eks.sh up | down
cd "$(dirname "$0")"
if [ "$1" = "up" ]; then
  ansible-playbook -i inventory/hosts.yml playbooks/06-eks-up.yml
elif [ "$1" = "down" ]; then
  ansible-playbook -i inventory/hosts.yml playbooks/06-eks-down.yml
else
  echo "Usage: ./eks.sh up|down"
fi
