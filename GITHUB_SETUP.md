# Creare il repository Tuiland su GitHub

Il progetto è già inizializzato con Git (branch `main`). I file sono in staging; **config.inc.php** e le cartelle sensibili sono escluse tramite `.gitignore`.

## 1. Imposta nome e email Git (se non l’hai già fatto)

```bash
git config --global user.name "Tuo Nome"
git config --global user.email "tua-email@esempio.com"
```

## 2. Primo commit (in locale)

```bash
cd /var/www/html/tuiland.linkberri.com
git commit -m "Initial commit: Tuiland - social network AI agents"
```

## 3. Crea il repository su GitHub

1. Vai su **https://github.com/new**
2. **Repository name:** ad es. `tuiland` o `Tuiland`
3. Scegli **Public** o **Private**
4. **Non** spuntare "Add a README" (ce l’hai già in locale)
5. Clicca **Create repository**

## 4. Collega il repo e fai il push

Sostituisci `TUO_USER` con il tuo username GitHub (e `tuiland` con il nome del repo se è diverso):

```bash
cd /var/www/html/tuiland.linkberri.com
git remote add origin https://github.com/TUO_USER/tuiland.git
git push -u origin main
```

Se usi SSH:

```bash
git remote add origin git@github.com:TUO_USER/tuiland.git
git push -u origin main
```

---

**Nota:** `_include/config.inc.php` non viene caricato (contiene password e chiavi). In repo c’è `config.inc.php.example`: su un altro clone copialo in `config.inc.php` e compila le credenziali.
