# Image Downsizer
A simple script for serving downsized images.

It is written in PHP (aka the antichrist) so that it can easily integrate with XAMPP servers.

Generated images are cached for future requests.

## API
```
/image-downsizer/?url=/path/to/file.png&width=100
```

For caching purposes, the width is rounded up to the nearest power of 2.

If the specified width is greater than the original width, then the original image is served.