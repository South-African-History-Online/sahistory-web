<script>
  import { onMount, createEventDispatcher } from 'svelte';
  
  export let events = [];
  export let minYear = 1300;
  export let maxYear = 2024;
  
  const dispatch = createEventDispatcher();
  
  let canvas;
  let ctx;
  let canvasWidth = 0;
  let canvasHeight = 400;
  let hoveredEvent = null;
  let tooltip = { x: 0, y: 0, visible: false };
  
  // Timeline configuration
  const padding = 60;
  const yearLabelHeight = 30;
  const eventRadius = 4;
  
  $: timelineWidth = canvasWidth - (padding * 2);
  $: yearRange = maxYear - minYear;
  $: pixelsPerYear = timelineWidth / yearRange;
  
  // Process events for canvas rendering
  $: processedEvents = events.map(event => {
    const eventDate = new Date(event.date);
    const eventYear = eventDate.getFullYear();
    const x = padding + ((eventYear - minYear) / yearRange) * timelineWidth;
    
    // Stack events that are close together
    const sameYearEvents = events.filter(e => 
      new Date(e.date).getFullYear() === eventYear
    );
    const eventIndex = sameYearEvents.findIndex(e => e.id === event.id);
    const y = canvasHeight / 2 + (eventIndex % 3 - 1) * 25;
    
    return {
      ...event,
      x,
      y,
      year: eventYear
    };
  });
  
  onMount(() => {
    if (canvas) {
      ctx = canvas.getContext('2d');
      resizeCanvas();
      draw();
    }
  });
  
  function resizeCanvas() {
    const rect = canvas.parentElement.getBoundingClientRect();
    canvasWidth = rect.width;
    canvas.width = canvasWidth * window.devicePixelRatio;
    canvas.height = canvasHeight * window.devicePixelRatio;
    canvas.style.width = canvasWidth + 'px';
    canvas.style.height = canvasHeight + 'px';
    ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
  }
  
  function draw() {
    if (!ctx) return;
    
    // Clear canvas
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
    
    // Draw timeline axis
    drawTimelineAxis();
    
    // Draw events
    drawEvents();
    
    // Draw year labels
    drawYearLabels();
  }
  
  function drawTimelineAxis() {
    ctx.strokeStyle = '#ddd';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(padding, canvasHeight / 2);
    ctx.lineTo(canvasWidth - padding, canvasHeight / 2);
    ctx.stroke();
  }
  
  function drawEvents() {
    processedEvents.forEach(event => {
      // Event circle
      ctx.fillStyle = getEventColor(event);
      ctx.beginPath();
      ctx.arc(event.x, event.y, eventRadius, 0, 2 * Math.PI);
      ctx.fill();
      
      // Highlight hovered event
      if (hoveredEvent && hoveredEvent.id === event.id) {
        ctx.strokeStyle = '#97212d';
        ctx.lineWidth = 2;
        ctx.stroke();
      }
      
      // Connection line to timeline
      if (event.y !== canvasHeight / 2) {
        ctx.strokeStyle = '#ccc';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(event.x, event.y);
        ctx.lineTo(event.x, canvasHeight / 2);
        ctx.stroke();
      }
    });
  }
  
  function drawYearLabels() {
    ctx.fillStyle = '#666';
    ctx.font = '12px sans-serif';
    ctx.textAlign = 'center';
    
    // Draw major year markers every 50 years
    for (let year = Math.ceil(minYear / 50) * 50; year <= maxYear; year += 50) {
      const x = padding + ((year - minYear) / yearRange) * timelineWidth;
      
      // Tick mark
      ctx.strokeStyle = '#999';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(x, canvasHeight / 2 - 10);
      ctx.lineTo(x, canvasHeight / 2 + 10);
      ctx.stroke();
      
      // Year label
      ctx.fillText(year.toString(), x, canvasHeight / 2 + 25);
    }
  }
  
  function getEventColor(event) {
    const colors = {
      'biography': '#3a4a64',
      'event': '#97212d',
      'article': '#2d5016',
      'archive': '#343a40'
    };
    return colors[event.type] || '#97212d';
  }
  
  function handleMouseMove(e) {
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    // Find hovered event
    const hovered = processedEvents.find(event => {
      const dx = x - event.x;
      const dy = y - event.y;
      return Math.sqrt(dx * dx + dy * dy) < eventRadius + 5;
    });
    
    if (hovered !== hoveredEvent) {
      hoveredEvent = hovered;
      
      if (hoveredEvent) {
        tooltip = {
          x: e.clientX,
          y: e.clientY,
          visible: true
        };
      } else {
        tooltip.visible = false;
      }
      
      draw();
    }
  }
  
  function handleClick(e) {
    if (hoveredEvent) {
      dispatch('select', hoveredEvent);
    }
  }
  
  // Redraw when events change
  $: if (ctx && events.length > 0) {
    draw();
  }
</script>

<svelte:window on:resize={() => {
  if (canvas) {
    resizeCanvas();
    draw();
  }
}} />

<div class="timeline-canvas-container">
  <canvas
    bind:this={canvas}
    on:mousemove={handleMouseMove}
    on:click={handleClick}
    style="cursor: {hoveredEvent ? 'pointer' : 'default'}"
  ></canvas>
  
  <!-- Tooltip -->
  {#if tooltip.visible && hoveredEvent}
    <div 
      class="tooltip"
      style="left: {tooltip.x + 10}px; top: {tooltip.y - 10}px;"
    >
      <strong>{hoveredEvent.title}</strong><br>
      <small>{new Date(hoveredEvent.date).getFullYear()}</small>
    </div>
  {/if}
</div>

<style>
  .timeline-canvas-container {
    width: 100%;
    height: 400px;
    position: relative;
    background: white;
    border-radius: 8px;
    margin: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  
  canvas {
    display: block;
    width: 100%;
    height: 100%;
  }
  
  .tooltip {
    position: fixed;
    background: rgba(0,0,0,0.9);
    color: white;
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    pointer-events: none;
    z-index: 1000;
    max-width: 200px;
    word-wrap: break-word;
  }
</style>