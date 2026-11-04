#!/bin/bash
echo "Stopping existing Nuxt on port 3020 if any..."
sudo fuser -k 3020/tcp || true
sleep 1
cd /home/agentuser/jurnal-kelas-digital-v2/frontend
PORT=3020 HOST=127.0.0.1 nohup node .output/server/index.mjs > /home/agentuser/jurnal-kelas-digital-v2/frontend/nuxt_server.log 2>&1 &
echo "Nuxt V2 running with PID: $!"
