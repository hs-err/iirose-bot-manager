pipeline {
    agent any

    stages {
        stage('checkout') {
            steps {
                checkout([
                    $class: 'GitSCM', 
                    branches: [[name: env.GIT_BUILD_REF]],
                    userRemoteConfigs: [[url: env.GIT_REPO_URL, credentialsId: env.CREDENTIALS_ID]]
                ])
            }
        }
        stage('Test') {
            steps {
                sh 'npm install'
                sh 'npm audit'
                sh 'npm run ci'
            }
        }
        stage('Archive') {
            steps {
                sh 'zip -r complete.zip ./*'
            }
        }
    }
    post{
        always{
            archiveArtifacts artifacts: 'complete.zip', allowEmptyArchive: true, fingerprint: true
        }
    }
}