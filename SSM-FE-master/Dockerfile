FROM node:alpine AS build
ARG NPM_TOKEN
ARG CONFIGURATION

WORKDIR /usr/src/app
COPY package.json package-lock.json ./
RUN echo "//registry.npmjs.org/:_authToken=${NPM_TOKEN}" > ./.npmrc \
    && npm install \
    && rm -f .npmrc
COPY . .
RUN $(npm bin)/ng build --prod --aot --configuration=$CONFIGURATION

### STAGE 2: Run ###
FROM nginx:alpine
COPY nginx.conf /etc/nginx/nginx.conf
COPY --from=build /usr/src/app/dist/SSM-FE /usr/share/nginx/html