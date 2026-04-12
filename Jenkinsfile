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
        def IMAGE_TAG = env.BRANCH_NAME.replaceAll(/[\\/_]/, '--') + "-${env.BUILD_NUMBER}"
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

        try {
            stage('Tests') {
                catchError(buildResult: 'FAILURE', stageResult: 'FAILURE') {
                    withEnv(['COMPOSE_FILE=compose.yaml']) {
                        sh 'make test-coverage'
                    }
                }
            }

            stage('Static Analysis') {
                catchError(buildResult: 'FAILURE', stageResult: 'FAILURE') {
                    withEnv(['COMPOSE_FILE=compose.yaml']) {
                        sh 'make phpstan'
                    }
                }
            }
        } finally {
            stage('Publish Reports') {
                junit allowEmptyResults: true, testResults: 'storage/test-reports/phpunit.xml'
                publishHTML(target: [
                    allowMissing: true,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: 'storage/test-reports/report',
                    reportFiles: 'index.html',
                    reportName: 'Code Coverage Report'
                ])
                archiveArtifacts allowEmptyArchive: true, artifacts: 'storage/test-reports/**', fingerprint: true
            }
        }

        if (currentBuild.currentResult != null && currentBuild.currentResult != 'SUCCESS') {
            error('Stopping pipeline after failed quality checks.')
        }

        stage('Build Docker Image') {
            sh "make build AWS_REGION=${AWS_REGION} AWS_ACCOUNT_ID=${AWS_ACCOUNT_ID} BUILD_NUMBER=${env.BUILD_NUMBER} GIT_BRANCH=${env.BRANCH_NAME} GIT_REV=${DOCKER_TAG}"
        }

        stage('Push to Docker Registry') {
            sh "make push AWS_REGION=${AWS_REGION} AWS_ACCOUNT_ID=${AWS_ACCOUNT_ID} BUILD_NUMBER=${env.BUILD_NUMBER} GIT_BRANCH=${env.BRANCH_NAME} GIT_REV=${DOCKER_TAG}"
        }

        stage('Update Helm Charts') {
            if (currentBuild.currentResult == 'SUCCESS' && BRANCH_NAME == 'master') {
                checkout scmGit(
                    branches: [[name: '*/master']],
                    extensions: [[$class: 'CleanCheckout'], [$class: 'RelativeTargetDirectory', relativeTargetDir: 'helm-charts']],
                    userRemoteConfigs: [[credentialsId: 'GITHUB_APP_JENKINS', url: 'https://github.com/apitree/helm-charts.git']]
                )

                dir('helm-charts') {
                    sh "sed -i 's/tag: \"[^\"]*\"/tag: \"${IMAGE_TAG}\"/' releases/prod/simpsons-quotes-api.yaml"
                    sh "git add releases/prod/simpsons-quotes-api.yaml"
                    sh "git commit -m 'Update ${PROJECT} to ${IMAGE_TAG} for prod deployment' || true"
                    sshagent(credentials: ['DEPLOY_KEY_JENKINS']) {
                        sh 'git push git@github.com:apitree/helm-charts.git HEAD:master'
                    }
                }
            }
        }
    }
}
