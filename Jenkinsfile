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
        docker logout 153806140965.dkr.ecr.eu-west-1.amazonaws.com || true
      '''
    }
  }
}
