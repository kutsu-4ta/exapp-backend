IMAGE = asia-northeast1-docker.pkg.dev/exapp-62f2c/exapp-backend/app:latest
REGION = asia-northeast1
PROJECT = exapp-62f2c
SERVICE = exapp-backend

deploy:
	docker build --platform linux/amd64 -t $(IMAGE) .
	docker push $(IMAGE)
	gcloud run deploy $(SERVICE) \
		--image $(IMAGE) \
		--region $(REGION) \
		--project $(PROJECT)
