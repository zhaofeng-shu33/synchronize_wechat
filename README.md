# wechat_synchronize_to_wordpress
synchronize wechat articles to wordpress website
## Intro
Nowadays, wechat posts are very common and people read traditional webpages in less frequency. However, for better search engine index and figure maintainance. Official website needs a technique to synchronize what they post on wechat public platform to their own website. By our search we do not find any open source solution. As a result, we try to implement a one. The official website we choose is based on `WordPress`, but we believe similar solutions can be provided and customized if the general pipeline is worked out. 

## Features we aimed

1. synchronize historical articles to wordpress website by just one click
2. synchronize current articles to wordpress website automatically

## Path to achieve our aim

1. Build Developping environment
1. Learn PHP Programming by Doing
1. Design Modules(PHP Functions) to achieve each sub-goals
1. Deployment on Real Environment and Testing

## How to build Developping Environment

### Prerequisite

1. You need an indepedent public IP address binded to a server and your wechat public domain(must be **authorized**).
2. The server can deploy WordPress as developping environment.

Note: The public IP address is used for the whitelist of the wechat public domain. Otherwise, you can not use the wechat api.

### Playing with the API

After successfully deploying the developping environment, you need to write some simple PHP functions to deal with the wechat api.
