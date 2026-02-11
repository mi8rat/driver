# Simple Roff Document Project

This project contains a sample Roff document that demonstrates basic text formatting capabilities.

## Files

- `sample.roff` - A simple roff document formatted as a manual page

## What is Roff?

Roff (short for "runoff") is a text formatting system used on Unix-like systems. It's most commonly used for creating man pages (manual pages) and technical documentation.

## Viewing the Document

You can view the formatted output using the `groff` or `man` commands:

### Method 1: Using groff
```bash
groff -man -Tascii sample.roff | less
```

### Method 2: Using groff with PostScript output
```bash
groff -man -Tps sample.roff > sample.ps
```

### Method 3: Using groff to generate PDF
```bash
groff -man -Tpdf sample.roff > sample.pdf
```

### Method 4: Preview as a man page
```bash
man ./sample.roff
```

## Basic Roff Macros Used

- `.TH` - Title heading (defines the title, section, date, version, and manual name)
- `.SH` - Section heading
- `.SS` - Subsection heading
- `.PP` - Paragraph break
- `.TP` - Tagged paragraph (used for option lists)
- `.IP` - Indented paragraph
- `.B` - Bold text
- `.I` - Italic text
- `.BR` - Bold-Roman alternating text
- `.nf/.fi` - No-fill mode (preserves formatting)

## Comments

Lines beginning with `.\\"` are comments and are not processed by roff.

## Further Reading

- Run `man groff` to learn more about groff
- Run `man 7 groff_man` to learn about the man page macro package
- Visit: https://www.gnu.org/software/groff/
