# Ansible AWS VPC Provisioning

Ce répertoire contient un playbook Ansible pour provisionner le réseau AWS de base du projet Symfony.

## Structure

- `inventory/hosts.yml` : inventaire local pour exécuter le playbook en connexion locale.
- `group_vars/all.yml` : variables communes du projet et AWS.
- `playbooks/01-vpc.yml` : playbook de provisionnement du VPC, sous-réseaux, IGW, route table et security group.
- `generated_vars.yml` : fichier généré après exécution contenant les IDs AWS créés.

## Exécution

Placez-vous dans le dossier `ansible` et lancez :

```bash
ansible-playbook -i inventory/hosts.yml playbooks/01-vpc.yml
```

## Résultats

Après exécution réussie, les IDs suivants seront enregistrés dans `ansible/generated_vars.yml` :

- `vpc_id`
- `subnet_ids`
- `security_group_id`

## RDS stop/start

Deux playbooks légers permettent de piloter l'état de l'instance RDS sans la recréer :

- `playbooks/rds-stop.yml` : passe l'instance en état `stopped` sans attendre l'arrêt complet.
- `playbooks/rds-start.yml` : redémarre l'instance avec `state: started` et attend qu'elle soit disponible.

Exécution :

```bash
cd ansible
ansible-playbook -i inventory/hosts.yml playbooks/rds-stop.yml
ansible-playbook -i inventory/hosts.yml playbooks/rds-start.yml
```

> Note importante : AWS RDS ne reste arrêté que 7 jours maximum. Au-delà, AWS redémarre automatiquement l'instance et la facturation continue jusqu'à ce que tu la stoppes de nouveau.

## Variables

Les variables AWS et projet sont définies dans `group_vars/all.yml`.

==>⚠️ Rappel : garde en tête que RDS ne reste "stopped" que 7 jours max avant qu'AWS ne le redémarre tout seul — donc si tu comptes faire une pause de plusieurs jours, pas de souci, mais au-delà d'une semaine, il faudra le restopper à ton retour.
