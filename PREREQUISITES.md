# Prerequisites
Install `Node.js` and the `PM2` daemon process manager:
```
# curl -fsSL https://deb.nodesource.com/setup_22.x -o nodesource_setup.sh
# bash nodesource_setup.sh
# apt install nodejs
# npm install -g npm
# npm install -g pm2@latest
# pm2 startup systemd -u peter --hp /home/peter
```
