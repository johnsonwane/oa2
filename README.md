# OA2

OA2 是一个用于 OpenAI Codex 工作流验证的最小化仓库脚手架。

## 目的

- 在一个干净的仓库中验证智能体执行能力。
- 演练提交与拉取请求（PR）自动化流程。
- 为后续项目初始化提供简单基线。

## Apache HTTPS 虚拟主机配置

```apache
<VirtualHost *:443>
    ServerName oac.hahahaxinli.com

    SSLEngine on
    SSLCertificateFile /opt/cert/oac.hahahaxinli.com.crt
    SSLCertificateKeyFile /opt/cert/oac.hahahaxinli.com.key
    SSLCertificateChainFile /opt/cert/root_bundle.crt

    # 前端静态文件放在域名根目录
    DocumentRoot /opt/webapps/oac.hahahaxinli.com/frontend
    <Directory /opt/webapps/oac.hahahaxinli.com/frontend>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>
</VirtualHost>
```
