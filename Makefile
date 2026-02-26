# =============================================================================
# Makefile for hello_driver — FreeBSD Kernel Module (Clang)
# =============================================================================
#
# PURPOSE
#   Builds an out-of-tree FreeBSD loadable kernel module (.ko file) using
#   Clang as the compiler and FreeBSD's standard bsd.kmod.mk build system.
#
# QUICK REFERENCE — available targets:
#   make              – compile hello_driver.ko from source
#   make clean        – delete all generated build artefacts
#   sudo make load    – insert the compiled module into the live kernel
#   sudo make unload  – remove the module from the live kernel
#   make test         – read a byte-stream from /dev/hello (needs load first)
#
# PREREQUISITES
#   1. FreeBSD 12+ (the driver API used here is stable on 12/13/14)
#   2. Kernel sources installed under /usr/src
#      (run: bsdinstall -> Sources, OR fetch via git/svn)
#      bsd.kmod.mk #include's headers from /usr/src/sys at build time.
#   3. Clang — already the default system compiler (cc) since FreeBSD 10.
# =============================================================================


# -----------------------------------------------------------------------------
# KMOD — Kernel MODule name
# -----------------------------------------------------------------------------
# This variable has two roles:
#   1. It tells bsd.kmod.mk what to name the output file  →  hello_driver.ko
#   2. It is used by kldload/kldunload (the load/unload targets below) as the
#      module identifier that the kernel tracks internally.
#
# Rule: must match the name passed to the DEV_MODULE() macro in the C source.
# -----------------------------------------------------------------------------
KMOD   = hello_driver


# -----------------------------------------------------------------------------
# SRCS — Source files to compile into the module
# -----------------------------------------------------------------------------
# List every .c (or .S for assembly) file that makes up the module.
# bsd.kmod.mk will compile each one with the correct kernel CFLAGS and then
# link them all together into a single relocatable .ko object.
#
# Multiple files example:
#   SRCS = hello_driver.c helper.c ioctl_handler.c
# -----------------------------------------------------------------------------
SRCS   = hello_driver.c


# -----------------------------------------------------------------------------
# CC / CXX — Compiler selection
# -----------------------------------------------------------------------------
# FreeBSD has used Clang as its in-tree compiler since version 10 (2014).
# Setting CC = clang here is mostly explicit documentation: it ensures Clang
# is used even on exotic setups where /usr/bin/cc might point to GCC.
#
# bsd.kmod.mk reads CC and passes it to every compilation step, so there is
# no need to repeat it per-file.
#
# CXX is defined for completeness; it would be used only if you add .cpp files
# to SRCS (unusual for kernel code, but technically possible).
# -----------------------------------------------------------------------------
CC     = clang
CXX    = clang++


# -----------------------------------------------------------------------------
# CWARNFLAGS — Extra compiler warning flags
# -----------------------------------------------------------------------------
# bsd.kmod.mk already enables a comprehensive set of -W flags for kernel code.
# CWARNFLAGS is appended to that set, so use += (not =) to avoid wiping the
# defaults.
#
# -Wno-unused-parameter
#   Many kernel callbacks (open, close, ioctl ...) receive parameters mandated
#   by the cdevsw interface that a simple driver doesn't use.  This suppresses
#   the warning for those intentionally unused arguments, keeping the build
#   output clean without requiring (void)param casts everywhere.
#
# Other useful flags you might add:
#   -Wextra            – enable additional GCC/Clang warnings
#   -Werror            – treat every warning as a hard error (CI-friendly)
#   -Wcast-align       – warn on pointer casts that increase alignment
# -----------------------------------------------------------------------------
CWARNFLAGS += -Wno-unused-parameter


# -----------------------------------------------------------------------------
# .include <bsd.kmod.mk>
# -----------------------------------------------------------------------------
# This is the heart of the FreeBSD out-of-tree module build system.
# The file lives at:  /usr/share/mk/bsd.kmod.mk
#
# What bsd.kmod.mk does for you automatically:
#
#   • Sets -D_KERNEL -DKLD_MODULE so kernel-only code paths are compiled.
#   • Adds -ffreestanding (no libc, no standard runtime startup).
#   • Adds -fno-builtin (prevent Clang from silently substituting builtins).
#   • On amd64: adds -mno-red-zone (the kernel cannot use the ABI red zone
#     because interrupts may fire at any time and clobber it).
#   • Selects the correct -target triple for cross-compilation if needed.
#   • Generates a module_syms.c stub that exports the module's ELF symbol
#     version info, allowing the kernel linker to validate ABI compatibility
#     at load time.
#   • Provides the default `all`, `clean`, `install`, and `depend` targets.
#   • Links the final .ko using ld(1) in relocatable (-r) mode so the kernel
#     can load it at an arbitrary address (position-independent by structure).
#
# IMPORTANT: .include must come AFTER all variable definitions above, because
# bsd.kmod.mk reads KMOD, SRCS, CC, CWARNFLAGS, etc. at include time.
# Defining them after the .include would have no effect.
# -----------------------------------------------------------------------------
.include <bsd.kmod.mk>


# =============================================================================
# CONVENIENCE TARGETS
# (These extend the standard targets provided by bsd.kmod.mk)
# =============================================================================

# .PHONY tells make that these target names are not files on disk.
# Without this, if a file called "load", "unload", or "test" ever existed in
# the directory, make would think the target was already up-to-date and skip
# running the recipe.
.PHONY: load unload test


# -----------------------------------------------------------------------------
# load — insert the compiled module into the running kernel
# -----------------------------------------------------------------------------
# Depends on ${KMOD}.ko so make will (re)compile the module first if the
# source has changed since the last build.
#
# kldload(8) arguments:
#   ./hello_driver.ko   – path to the .ko file.  The "./" prefix is required
#                         when loading from the current directory because
#                         kldload does NOT search "." by default; it looks in
#                         /boot/kernel and /boot/modules.
#
# Requires root — run as: sudo make load
# -----------------------------------------------------------------------------
load: ${KMOD}.ko
	kldload ./${KMOD}.ko


# -----------------------------------------------------------------------------
# unload — remove the module from the live kernel
# -----------------------------------------------------------------------------
# kldunload(8) takes the module name (not the file path).
# The name must match the string passed to DEV_MODULE() in the C source,
# which is stored in the module's ELF metadata.
#
# Use `kldstat` to list all currently loaded modules and their names.
#
# Requires root — run as: sudo make unload
# -----------------------------------------------------------------------------
unload:
	kldunload ${KMOD}


# -----------------------------------------------------------------------------
# test — perform a simple read from the character device
# -----------------------------------------------------------------------------
# Reads bytes from /dev/hello, which our driver serves.
# The @ prefix on the echo command suppresses make from printing the command
# itself before executing it, giving cleaner output.
#
# cat(1) opens the device, reads until EOF (our driver returns 0 bytes once
# the greeting string has been consumed), then closes it — exercising the
# hello_open, hello_read, and hello_close callbacks in the driver.
#
# The module must be loaded first (sudo make load) for /dev/hello to exist.
# -----------------------------------------------------------------------------
test:
	@echo "--- reading /dev/hello ---"
	cat /dev/hello
