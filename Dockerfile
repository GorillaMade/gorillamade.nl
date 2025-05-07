FROM klakegg/hugo:ext-alpine

WORKDIR /app
COPY . .
RUN hugo --minify

CMD ["hugo", "server", "--bind", "0.0.0.0", "--port", "3000"]
