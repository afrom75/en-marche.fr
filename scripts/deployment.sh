#!/bin/bash
set -e

# Get credentials
sudo /opt/google-cloud-sdk/bin/gcloud --quiet components update kubectl
sudo /opt/google-cloud-sdk/bin/gcloud container clusters get-credentials $GCLOUD_CLUSTER --project $GCLOUD_PROJECT --zone europe-west1-d

# Migrates database
export GOOGLE_APPLICATION_CREDENTIALS=$HOME/gcloud-service-key.json
sudo /opt/google-cloud-sdk/bin/kubectl set image pod/staging-migrate-tasks enmarche=eu.gcr.io/$GCLOUD_PROJECT/app:$CIRCLE_SHA1
sudo /opt/google-cloud-sdk/bin/kubectl get pod staging-migrate-tasks -o yaml | sudo /opt/google-cloud-sdk/bin/kubectl replace --force -f -

# Send result to slack
migration_log=$(/opt/google-cloud-sdk/bin/kubectl logs staging-migrate-tasks --container=enmarche)
json="{\"text\": \"\`\`\`$(echo $migration_log | sed 's/"//g' | sed "s/'//g" | sed 's/\\/\//g' )\`\`\`\"}"
curl -s "Content-Type: application/json" -d "payload=$json" https://hooks.slack.com/services/$SLACK_TOKEN

# Deploy to staging
declare -a images=("staging-app" "staging-worker-mailjet-campaign" "staging-worker-mailjet-transactional" "staging-worker-referent")

for image in "${images[@]}"
do
   sudo /opt/google-cloud-sdk/bin/kubectl set image deployment/$image enmarche=eu.gcr.io/$GCLOUD_PROJECT/app:$CIRCLE_SHA1
done
