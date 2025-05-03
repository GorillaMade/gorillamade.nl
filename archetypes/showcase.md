---
title: "{{ replace .Name "-" " " | title }}"
date: {{ .Date }}
description: ""
tags: []
categories: ["showcase"]
featured: false
images:
  thumbnail: "/images/showcases/{{ .Name }}-thumb.png"
  full:
    src: "/images/showcases/{{ .Name }}-full.png"
    background: "#ffffff"
client: ""
project_url: ""
draft: true
---