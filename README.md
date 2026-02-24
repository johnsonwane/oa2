# OA2

这是一个培训公司 OA 系统样例，采用**前后端分离**：

- 前端（uni-app Vue3 项目）目录：`/opt/webapps/oac.hahahaxinli.com/`
- 后端（PHP 无框架）目录：`/opt/webapps/oac.hahahaxinli.com/api/`
- 前端访问域名：`https://oac.hahahaxinli.com`
- 后端接口域名：`https://oac.hahahaxinli.com/api`

## 前端说明（uni-app Vue3）

前端不是简单静态页面，而是一个 uni-app Vue3 项目，核心文件如下：

```text
opt/webapps/oac.hahahaxinli.com/
├── App.vue
├── main.js
├── manifest.json
├── pages.json
├── uni.scss
├── pages/
│   ├── login/login.vue
│   └── dashboard/dashboard.vue
└── utils/
    └── request.js
```

### 当前样例页面

- 登录页：`pages/login/login.vue`
- 仪表盘页：`pages/dashboard/dashboard.vue`

## 后端接口

- 登录接口：`POST /api/login.php`
- 样例账号：`admin / 123456`

> 说明：当前登录逻辑是样例实现，账号密码为硬编码。生产环境请改为数据库用户体系与安全认证方案。

## Apache HTTPS 虚拟主机配置

```apache
<VirtualHost *:443>
    ServerName oac.hahahaxinli.com

    SSLEngine on
    SSLCertificateFile /opt/cert/oac.hahahaxinli.com.crt
    SSLCertificateKeyFile /opt/cert/oac.hahahaxinli.com.key
    SSLCertificateChainFile /opt/cert/root_bundle.crt

    DocumentRoot /opt/webapps/oac.hahahaxinli.com
    <Directory /opt/webapps/oac.hahahaxinli.com>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>
</VirtualHost>
```
