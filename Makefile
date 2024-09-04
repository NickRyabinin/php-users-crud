build:
	mkdir -p ./app/src/
	mkdir -p ./db/
	docker-compose up --build
run:
	docker-compose up -d
stop:
	docker-compose stop