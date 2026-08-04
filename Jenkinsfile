pipeline {
  agent any
  environment {
    ECR_REG = '153806140965.dkr.ecr.eu-west-1.amazonaws.com/symfony-restaurant-app'
    IMAGE_NAME = 'symfony-restaurant-app'
  }
  stages {
    stage('Checkout') {
      steps {
        checkout scm
      }
    }

    stage('Build Docker Image') {
      steps {
        sh "docker build -t ${IMAGE_NAME}:${BUILD_NUMBER} -t ${IMAGE_NAME}:latest ."
      }
    }

    stage('Push to ECR') {
      steps {
        withCredentials([[ $class: 'AmazonWebServicesCredentialsBinding', credentialsId: 'aws-credentials' ]]) {
          sh '''
            aws ecr get-login-password --region eu-west-1 | docker login --username AWS --password-stdin 153806140965.dkr.ecr.eu-west-1.amazonaws.com
            docker tag ${IMAGE_NAME}:${BUILD_NUMBER} ${ECR_REG}:${BUILD_NUMBER}
            docker tag ${IMAGE_NAME}:latest ${ECR_REG}:latest
            docker push ${ECR_REG}:${BUILD_NUMBER}
            docker push ${ECR_REG}:latest
          '''
        }
      }
    }

    stage('Deploy to EKS') {
      steps {
        withCredentials([
            string(credentialsId: 'rds-password', variable: 'RDS_PASSWORD'),
            string(credentialsId: 'app-secret', variable: 'APP_SECRET'),
            string(credentialsId: 'google-client-id', variable: 'GOOGLE_CLIENT_ID'),
            string(credentialsId: 'google-client-secret', variable: 'GOOGLE_CLIENT_SECRET'),
            [$class: 'AmazonWebServicesCredentialsBinding', credentialsId: 'aws-credentials']
        ]) {
          sh '''
            sed -e "s|__RDS_PASSWORD__|$RDS_PASSWORD|g" \
                -e "s|__APP_SECRET__|$APP_SECRET|g" \
                -e "s|__GOOGLE_CLIENT_ID__|$GOOGLE_CLIENT_ID|g" \
                -e "s|__GOOGLE_CLIENT_SECRET__|$GOOGLE_CLIENT_SECRET|g" \
                k8s/secret.yaml.template > k8s/secret.yaml

            echo 'DRY-RUN configmap'
            kubectl apply --dry-run=server -f k8s/configmap.yaml -n symfony-restaurant || true
            kubectl apply -f k8s/configmap.yaml

            echo 'DRY-RUN secret'
            kubectl apply --dry-run=server -f k8s/secret.yaml -n symfony-restaurant || true
            kubectl apply -f k8s/secret.yaml

            echo 'DRY-RUN nginx configmap'
            kubectl apply --dry-run=server -f k8s/nginx-configmap.yaml -n symfony-restaurant || true
            kubectl apply -f k8s/nginx-configmap.yaml

            echo 'DRY-RUN deployment'
            kubectl apply --dry-run=server -f k8s/deployment.yaml -n symfony-restaurant || true
            kubectl apply -f k8s/deployment.yaml

            echo 'DRY-RUN service'
            kubectl apply --dry-run=server -f k8s/service.yaml -n symfony-restaurant || true
            kubectl apply -f k8s/service.yaml

            echo 'DRY-RUN ingress'
            kubectl apply --dry-run=server -f k8s/ingress.yaml -n symfony-restaurant || true
            kubectl apply -f k8s/ingress.yaml

            # Update deployment image to the build tag and wait for rollout
            kubectl set image deployment/symfony-restaurant-app php-fpm=${ECR_REG}:${BUILD_NUMBER} -n symfony-restaurant
            kubectl rollout status deployment/symfony-restaurant-app -n symfony-restaurant --timeout=180s

            rm -f k8s/secret.yaml
          '''
        }
      }
    }
  }
  post {
    success {
      echo "Build and push successful: ${ECR_REG}:${BUILD_NUMBER}"
    }
    failure {
      echo "Build or push failed — check console output for details."
    }
    always {
      sh '''
        docker image rm -f ${IMAGE_NAME}:${BUILD_NUMBER} || true
        docker image rm -f ${IMAGE_NAME}:latest || true
        docker system prune -af --volumes --filter "until=24h" || true
        docker logout 153806140965.dkr.ecr.eu-west-1.amazonaws.com || true
      '''
    }
  }
}
