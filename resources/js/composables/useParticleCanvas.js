export function useParticleCanvas(canvasRef) {
  const PARTICLE_COUNT       = 70
  const PARTICLE_RADIUS      = 2
  const PARTICLE_SPEED       = 0.5
  const CONNECTION_THRESHOLD = 120
  const BG_COLOR             = '#242933'
  const PARTICLE_RGB         = '216, 222, 233'
  const PARTICLE_COLOR       = `rgba(${PARTICLE_RGB}, 0.85)`
  const LINE_BASE_OPACITY    = 0.3

  let rafHandle = null
  let particles  = []
  let observer   = null
  let ctx        = null

  /** Scatter N particles randomly across the given dimensions. */
  function scatter(width, height) {
    particles = Array.from({ length: PARTICLE_COUNT }, () => {
      const angle = Math.random() * Math.PI * 2
      return {
        x:  Math.random() * width,
        y:  Math.random() * height,
        vx: Math.cos(angle) * PARTICLE_SPEED,
        vy: Math.sin(angle) * PARTICLE_SPEED,
      }
    })
  }

  /** One animation frame: clear → move → lines → dots. */
  function draw() {
    const canvas = canvasRef.value
    if (!canvas || !ctx) return

    const width  = canvas.width
    const height = canvas.height

    // 1. Clear
    ctx.fillStyle = BG_COLOR
    ctx.fillRect(0, 0, width, height)

    // 2. Move particles (toroidal wrap)
    for (const p of particles) {
      p.x += p.vx
      p.y += p.vy
      if (p.x < 0)      p.x += width
      if (p.x > width)  p.x -= width
      if (p.y < 0)      p.y += height
      if (p.y > height) p.y -= height
    }

    // 3. Draw connection lines
    ctx.lineWidth = 1
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx   = particles[i].x - particles[j].x
        const dy   = particles[i].y - particles[j].y
        const dist = Math.sqrt(dx * dx + dy * dy)
        if (dist < CONNECTION_THRESHOLD) {
          const opacity = (1 - dist / CONNECTION_THRESHOLD) * LINE_BASE_OPACITY
          ctx.strokeStyle = `rgba(${PARTICLE_RGB}, ${opacity.toFixed(3)})`
          ctx.beginPath()
          ctx.moveTo(particles[i].x, particles[i].y)
          ctx.lineTo(particles[j].x, particles[j].y)
          ctx.stroke()
        }
      }
    }

    // 4. Draw particle dots
    ctx.fillStyle = PARTICLE_COLOR
    for (const p of particles) {
      ctx.beginPath()
      ctx.arc(p.x, p.y, PARTICLE_RADIUS, 0, Math.PI * 2)
      ctx.fill()
    }

    rafHandle = requestAnimationFrame(draw)
  }

  /**
   * Call in onMounted.
   * Synchronously sizes the canvas, scatters particles, starts the RAF loop.
   * If canvasRef.value is null, returns immediately (no-op).
   */
  function init() {
    const canvas = canvasRef.value
    if (!canvas) return

    const parent = canvas.parentElement
    if (parent.clientWidth === 0) return
    canvas.width    = parent.clientWidth
    canvas.height   = parent.clientHeight

    ctx = canvas.getContext('2d')
    scatter(canvas.width, canvas.height)
    rafHandle = requestAnimationFrame(draw)

    observer = new ResizeObserver(() => {
      canvas.width  = parent.clientWidth
      canvas.height = parent.clientHeight
      // Resizing the canvas bitmap resets its drawing state but does NOT
      // invalidate the 2D context — ctx remains usable without re-calling
      // getContext('2d'). Particles keep their positions; toroidal wrap
      // corrects any out-of-bounds state on the next animation frame.
    })
    observer.observe(parent)
  }

  /**
   * Call in onUnmounted.
   * Cancels the RAF loop and disconnects the ResizeObserver.
   */
  function cleanup() {
    if (rafHandle !== null) {
      cancelAnimationFrame(rafHandle)
      rafHandle = null
    }
    if (observer) {
      observer.disconnect()
      observer = null
    }
  }

  return { init, cleanup }
}
