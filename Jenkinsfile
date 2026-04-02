#!groovy

def AWS_REGION = "eu-central-1"
def VENDOR = "slin-master"
def PROJECT = "simpsons-quotes-api"

node('docker-agent') {
    ansiColor('xterm') {
        properties([
            buildDiscarder(logRotator(artifactDaysToKeepStr: '', artifactNumToKeepStr: '', daysToKeepStr: '14', numToKeepStr: '20')),
            githubProjectProperty(projectUrlStr: "https://github.com/${VENDOR}/${PROJECT}"),
        ])

        checkout scm
        def DOCKER_TAG = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
        def AWS_ACCOUNT_ID = sh(script: "aws sts get-caller-identity --query Account --output text", returnStdout: true).trim()
        def DOCKER_REPO_URL = "${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"
        def DEPLOY_TAG = env.BRANCH_NAME == 'master' ? 'latest' : env.BRANCH_NAME.replaceAll(/[\\/_]/, '--') + "-${env.BUILD_NUMBER}"
        def IMAGE_NAME = "${DOCKER_REPO_URL}/${VENDOR}/${PROJECT}:${DEPLOY_TAG}"
        echo "Running build ${env.BUILD_NUMBER} on ${env.JENKINS_URL} for ${env.BRANCH_NAME} (${DOCKER_TAG})"
        currentBuild.description = "${env.BRANCH_NAME}-${DOCKER_TAG}-${env.BUILD_NUMBER}"

        sh "make info AWS_REGION=${AWS_REGION} AWS_ACCOUNT_ID=${AWS_ACCOUNT_ID} BUILD_NUMBER=${env.BUILD_NUMBER} GIT_BRANCH=${env.BRANCH_NAME} GIT_REV=${DOCKER_TAG}"

        retry(10) {
            sh 'docker info >/dev/null 2>&1 || { echo "Docker daemon not ready - retrying..."; sleep 5; exit 1; }'
        }

        stage('Build CI Image') {
            withEnv(['COMPOSE_FILE=compose.yaml']) {
                sh 'make build-ci-image'
            }
        }

        stage('Tests') {
            withEnv(['COMPOSE_FILE=compose.yaml']) {
                sh 'make test-coverage'
            }
        }

        stage('Static Analysis') {
            withEnv(['COMPOSE_FILE=compose.yaml']) {
                sh 'make phpstan'
            }
        }

        stage('Publish Reports') {
            junit allowEmptyResults: false, testResults: 'storage/test-reports/phpunit.xml'
            publishHTML(target: [
                allowMissing: false,
                alwaysLinkToLastBuild: true,
                keepAll: true,
                reportDir: 'storage/test-reports/report',
                reportFiles: 'index.html',
                reportName: 'Code Coverage Report'
            ])
            archiveArtifacts artifacts: 'storage/test-reports/**', fingerprint: true
        }

        stage('Build Docker Image') {
            sh "make build AWS_REGION=${AWS_REGION} AWS_ACCOUNT_ID=${AWS_ACCOUNT_ID} BUILD_NUMBER=${env.BUILD_NUMBER} GIT_BRANCH=${env.BRANCH_NAME} GIT_REV=${DOCKER_TAG}"
        }

        stage('Push to Docker Registry') {
            sh "make push AWS_REGION=${AWS_REGION} AWS_ACCOUNT_ID=${AWS_ACCOUNT_ID} BUILD_NUMBER=${env.BUILD_NUMBER} GIT_BRANCH=${env.BRANCH_NAME} GIT_REV=${DOCKER_TAG}"
        }

        def USER = "bix"
        def HOST = "ferrix"

        stage("Deploy to ${HOST}") {
            sshagent(credentials: [HOST]) {
                sh "ssh -o StrictHostKeyChecking=no ${USER}@${HOST} 'mkdir -p /data/${PROJECT}/data/storage/database /data/${PROJECT}/data/storage/logs'"
                sh "scp -o StrictHostKeyChecking=no ${env.WORKSPACE}/compose.yaml ${USER}@${HOST}:/data/${PROJECT}/compose.yaml"
                sh "ssh -o StrictHostKeyChecking=no ${USER}@${HOST} 'printf \"IMAGE_NAME=${IMAGE_NAME}\\n\" > /data/${PROJECT}/.env'"
                sh "ssh -o StrictHostKeyChecking=no ${USER}@${HOST} 'cd /data/${PROJECT} && docker compose pull && docker compose up -d'"
            }
        }
    }
}
