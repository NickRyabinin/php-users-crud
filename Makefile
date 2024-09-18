build:
	mkdir -p ./app/src/
	mkdir -p ./db/
	mkdir -p ./app/logs/
	touch ./app/logs/error.log
	docker-compose up --build
run:
	docker-compose up -d
stop:
	docker-compose stop