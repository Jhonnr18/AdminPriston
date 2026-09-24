# Publicar AdminPriston no GitHub

O token do cloud agent **não tem** permissão `createRepository`.

1. No GitHub (conta **Jhonnr18**): New repository → nome **AdminPriston** → **Private** → **sem** README/gitignore/license (vazio).
2. Depois rode:

```bash
cd /home/ubuntu/AdminPriston
git push -u origin main
```

Ou, se o remote ainda não existir:

```bash
git remote add origin https://github.com/Jhonnr18/AdminPriston.git
git push -u origin main
```

Artifact com `.git`: `/opt/cursor/artifacts/AdminPriston-with-git.tar.gz`
