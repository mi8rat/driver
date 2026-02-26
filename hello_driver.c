/*
 * hello_driver.c - A simple FreeBSD character device kernel driver
 *
 * Build:  make
 * Load:   kldload ./hello_driver.ko
 * Test:   cat /dev/hello
 * Unload: kldunload hello_driver
 */

#include <sys/param.h>
#include <sys/module.h>
#include <sys/kernel.h>
#include <sys/systm.h>
#include <sys/conf.h>
#include <sys/uio.h>
#include <sys/malloc.h>

/* ------------------------------------------------------------------ */
/* Driver state                                                         */
/* ------------------------------------------------------------------ */

#define DRIVER_NAME   "hello"
#define HELLO_MSG     "Hello from the FreeBSD kernel!\n"
#define HELLO_MSG_LEN (sizeof(HELLO_MSG) - 1)

static d_open_t  hello_open;
static d_close_t hello_close;
static d_read_t  hello_read;

static struct cdevsw hello_cdevsw = {
    .d_version = D_VERSION,
    .d_open    = hello_open,
    .d_close   = hello_close,
    .d_read    = hello_read,
    .d_name    = DRIVER_NAME,
};

static struct cdev *hello_dev;

/* ------------------------------------------------------------------ */
/* cdev operations                                                      */
/* ------------------------------------------------------------------ */

static int
hello_open(struct cdev *dev, int oflags, int devtype, struct thread *td)
{
    uprintf("[hello_driver] Device opened.\n");
    return (0);
}

static int
hello_close(struct cdev *dev, int fflag, int devtype, struct thread *td)
{
    uprintf("[hello_driver] Device closed.\n");
    return (0);
}

static int
hello_read(struct cdev *dev, struct uio *uio, int ioflag)
{
    size_t amt;
    int    error;

    /* Only serve as many bytes as are left in our message */
    amt = MIN(uio->uio_resid, (HELLO_MSG_LEN - uio->uio_offset > 0)
              ? HELLO_MSG_LEN - uio->uio_offset : 0);

    if (amt == 0)
        return (0);

    error = uiomove(__DECONST(void *, HELLO_MSG + uio->uio_offset), amt, uio);
    if (error)
        uprintf("[hello_driver] uiomove failed.\n");

    return (error);
}

/* ------------------------------------------------------------------ */
/* Module lifecycle                                                     */
/* ------------------------------------------------------------------ */

static int
hello_modevent(module_t mod __unused, int event, void *arg __unused)
{
    int error = 0;

    switch (event) {
    case MOD_LOAD:
        hello_dev = make_dev(&hello_cdevsw,
                             0,
                             UID_ROOT, GID_WHEEL, 0444,
                             DRIVER_NAME);
        printf("[hello_driver] Loaded. /dev/%s is ready.\n", DRIVER_NAME);
        break;

    case MOD_UNLOAD:
        destroy_dev(hello_dev);
        printf("[hello_driver] Unloaded.\n");
        break;

    default:
        error = EOPNOTSUPP;
        break;
    }

    return (error);
}

DEV_MODULE(hello_driver, hello_modevent, NULL);
