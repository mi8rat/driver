# hello_driver — Simple FreeBSD Kernel Module (Clang)

A minimal character-device kernel driver for FreeBSD, built with Clang and
the standard `bsd.kmod.mk` build infrastructure.

## Files

| File               | Purpose                                    |
|--------------------|--------------------------------------------|
| `hello_driver.c`   | Kernel module source (character device)    |
| `Makefile`         | Build rules — uses Clang + bsd.kmod.mk     |

---

## Requirements

* FreeBSD 12 or later (tested on 13/14)
* Clang — already the default system compiler on FreeBSD 10+
* Kernel headers: installed as part of `src` (`/usr/src`)  
  If missing: `bsdinstall` → **Sources**

---

## Build

```sh
make
```

This produces `hello_driver.ko`.

---

## Load & test

```sh
# Load the module
sudo make load
# or: sudo kldload ./hello_driver.ko

# Verify it's loaded
kldstat | grep hello

# Read from the device
cat /dev/hello          # prints: Hello from the FreeBSD kernel!

# Unload
sudo make unload
# or: sudo kldunload hello_driver
```

---

## How it works

| Layer            | Detail                                                   |
|------------------|----------------------------------------------------------|
| Module entry     | `DEV_MODULE` macro registers `hello_modevent`            |
| `MOD_LOAD`       | `make_dev()` creates `/dev/hello` (mode 0444)            |
| `d_read`         | `uiomove()` copies the greeting string to user-space     |
| `MOD_UNLOAD`     | `destroy_dev()` removes `/dev/hello`                     |

---

## Clang-specific notes

`bsd.kmod.mk` honours the `CC` variable, so setting `CC = clang` in the
Makefile is enough. On FreeBSD, Clang is `cc` by default, so the explicit
assignment is mainly for clarity and portability if someone runs this on a
system where `cc` resolves to GCC.

The kernel build system passes the correct `-target`, `-ffreestanding`,
`-fno-builtin`, and `-mno-red-zone` flags automatically via `bsd.kmod.mk`
— you do not need to set those manually.
