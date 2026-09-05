FROM node:20-alpine

WORKDIR /app

RUN apk add --no-cache git

COPY package.json package-lock.json* ./
RUN npm ci --omit=dev

COPY src ./src

ENV NODE_ENV=production
ENV PORT=3000
ENV AUTH_DIR=/data/sessions

RUN mkdir -p /data/sessions

EXPOSE 3000

CMD ["node", "src/index.js"]
