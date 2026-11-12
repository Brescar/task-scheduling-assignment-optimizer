FROM node:22-alpine

# Install system utilities
RUN apk add --no-cache bash curl git

WORKDIR /app

ENV HOST=0.0.0.0
ENV PORT=3000
ENV NODE_ENV=development

EXPOSE 3000

# Start Nuxt in development mode with hot module reloading; ensure dependencies are installed
CMD ["sh", "-c", "if [ ! -f node_modules/.bin/nuxt ] && [ -f package.json ]; then npm install; fi && npm run dev -- --host 0.0.0.0"]
