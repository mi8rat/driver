# Makefile for hello_driver — FreeBSD kernel module (Clang)
#
# Usage:
#   make              – build hello_driver.ko
#   make clean        – remove build artefacts
#   sudo make load    – load the module into the running kernel
#   sudo make unload  – unload the module
#   make test         – read from /dev/hello (module must be loaded)

# --------------------------------------------------------------------------- #
# FreeBSD kernel build infrastructure uses src.conf / kbuild.mk.              #
# The standard way to build out-of-tree modules is via kld(4) + bsd.kmod.mk.  #
# --------------------------------------------------------------------------- #

# Name of the kernel module (without .ko)
KMOD   = hello_driver

# Source files
SRCS   = hello_driver.c

# ---- Compiler selection ---------------------------------------------------
# FreeBSD ships Clang as the system compiler since FreeBSD 10.
# Set CC/CXX explicitly so the build always uses Clang even if the user's
# PATH puts GCC first.
CC     = clang
CXX    = clang++

# Extra warning flags (optional, but good practice for driver code)
CWARNFLAGS += -Wno-unused-parameter

# ---- Bring in the standard FreeBSD kernel-module build rules ---------------
# bsd.kmod.mk lives at /usr/share/mk/bsd.kmod.mk on every FreeBSD system.
.include <bsd.kmod.mk>

# --------------------------------------------------------------------------- #
# Convenience targets (not part of the standard kbuild)                       #
# --------------------------------------------------------------------------- #

.PHONY: load unload test

load: ${KMOD}.ko
	kldload ./${KMOD}.ko

unload:
	kldunload ${KMOD}

test:
	@echo "--- reading /dev/hello ---"
	cat /dev/hello
