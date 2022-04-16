#!/usr/bin/env bash
path=$(dirname "$0")
docker="$path"
root="$docker/.."
public="$root/public"
protected="$public/protected"
config="$protected/config"
#sudo chmod -R a+rw "$docker/database/logs"
#sudo chmod -R a+rw "$docker/database/store"
sudo chmod -R a+rw "$protected/runtime"
sudo chmod -R a+rw "$protected/data"
sudo chmod -R a+rw "$public/assets"
if [ ! -f "$config/server_specific.php" ]; then
	cp -a "$config/server_specific-sample.php" "$config/server_specific.php"
fi
