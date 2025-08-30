<script>
  import { onMount, createEventDispatcher, afterUpdate } from 'svelte';
  import { spring, tweened } from 'svelte/motion';
  import { cubicInOut } from 'svelte/easing';
  
  export let events = [];
  export let minYear = 1300;
  export let maxYear = 2024;
  
  const dispatch = createEventDispatcher();
  
  let canvas;
  let ctx;
  let canvasWidth = 0;
  let canvasHeight = 500;
  let hoveredEvent = null;
  let tooltip = { x: 0, y: 0, visible: false };
  let animationFrame = null;
  
  // Modern interactive timeline configuration
  const padding = 80;
  const yearLabelHeight = 40;
  const baseEventRadius = 5;
  const hoveredEventRadius = 8;
  const maxEventRadius = 12;
  
  // Zoom and pan functionality
  let zoom = spring(1, { stiffness: 0.1, damping: 0.8 });
  let panX = spring(0, { stiffness: 0.1, damping: 0.8 });
  let viewportStart = tweened(minYear, { duration: 800, easing: cubicInOut });
  let viewportEnd = tweened(maxYear, { duration: 800, easing: cubicInOut });
  
  // Animation state
  let animationProgress = tweened(0, { duration: 1200, easing: cubicInOut });
  
  // Historical periods with modern colour palette
  const historicalPeriods = [
    { name: 'Pre-Colonial', start: 1300, end: 1650, colour: '#8B7355', gradient: ['#A0956B', '#8B7355'] },
    { name: 'Colonial', start: 1650, end: 1910, colour: '#5B7C99', gradient: ['#7A9BC4', '#5B7C99'] },
    { name: 'Union Era', start: 1910, end: 1948, colour: '#C8860D', gradient: ['#E6A434', '#C8860D'] },
    { name: 'Apartheid', start: 1948, end: 1994, colour: '#B22222', gradient: ['#D64545', '#B22222'] },
    { name: 'Democracy', start: 1994, end: 2024, colour: '#228B22', gradient: ['#32CD32', '#228B22'] }
  ];
  
  $: timelineWidth = canvasWidth - (padding * 2);
  $: yearRange = $viewportEnd - $viewportStart;
  $: pixelsPerYear = timelineWidth / yearRange;
  
  // Advanced event clustering and level-of-detail processing for large datasets
  $: processedEvents = (() => {
    if (!events || events.length === 0) return [];
    
    // Performance optimisation: only process events in extended viewport
    const bufferYears = Math.max(50, yearRange * 0.2);
    const extendedStart = $viewportStart - bufferYears;
    const extendedEnd = $viewportEnd + bufferYears;
    
    // Filter events within extended viewport first
    const visibleEvents = events.filter(event => {
      const eventYear = new Date(event.date).getFullYear();
      return eventYear >= extendedStart && eventYear <= extendedEnd;
    });
    
    // Calculate level of detail based on zoom level and event density
    const pixelsPerEvent = timelineWidth / Math.max(1, visibleEvents.length);
    const levelOfDetail = pixelsPerEvent < 1.5 ? 'aggregate' : pixelsPerEvent < 6 ? 'cluster' : 'full';
    
    if (levelOfDetail === 'aggregate') {
      return createAggregatedEvents(visibleEvents);
    } else if (levelOfDetail === 'cluster') {
      return createClusteredEvents(visibleEvents);
    } else {
      return createFullDetailEvents(visibleEvents);
    }
  })();
  
  function createAggregatedEvents(events) {
    // Aggregate events into time buckets for extreme zoom out
    const bucketSize = Math.max(5, Math.round(yearRange / 40));
    const buckets = {};
    
    events.forEach(event => {
      const eventYear = new Date(event.date).getFullYear();
      const bucketYear = Math.floor(eventYear / bucketSize) * bucketSize;
      
      if (!buckets[bucketYear]) {
        buckets[bucketYear] = {
          year: bucketYear,
          endYear: bucketYear + bucketSize - 1,
          count: 0,
          events: [],
          periods: new Set(),
          types: new Set(),
          themes: new Set()
        };
      }
      
      buckets[bucketYear].count++;
      buckets[bucketYear].events.push(event);
      const period = historicalPeriods.find(p => eventYear >= p.start && eventYear <= p.end);
      if (period) buckets[bucketYear].periods.add(period);
      buckets[bucketYear].types.add(event.type);
      event.themes?.forEach(theme => buckets[bucketYear].themes.add(theme));
    });
    
    return Object.values(buckets).map(bucket => {
      const x = padding + ((bucket.year - $viewportStart) / yearRange) * timelineWidth;
      const primaryPeriod = Array.from(bucket.periods)[0];
      
      // Dynamic sizing based on event density (logarithmic scale for large numbers)
      const baseSize = baseEventRadius + 2;
      const densityMultiplier = Math.min(3, Math.log10(bucket.count + 1));
      const radius = baseSize + densityMultiplier * 4;
      
      return {
        id: `aggregate-${bucket.year}`,
        x,
        y: canvasHeight / 2,
        year: bucket.year,
        radius: radius * $animationProgress,
        baseRadius: radius,
        period: primaryPeriod,
        isAggregate: true,
        count: bucket.count,
        events: bucket.events.slice(0, 10), // Limit for performance
        title: `${bucket.count} events (${bucket.year}-${bucket.endYear})`,
        themes: Array.from(bucket.themes).slice(0, 3)
      };
    });
  }
  
  function createClusteredEvents(events) {
    // Smart clustering for medium zoom levels
    const clusters = [];
    const yearThreshold = Math.max(0.5, yearRange / 300);
    
    // Sort events by year for clustering
    const sortedEvents = [...events].sort((a, b) => 
      new Date(a.date).getFullYear() - new Date(b.date).getFullYear()
    );
    
    sortedEvents.forEach(event => {
      const eventYear = new Date(event.date).getFullYear();
      
      // Skip events outside viewport
      if (eventYear < $viewportStart || eventYear > $viewportEnd) return;
      
      const x = padding + ((eventYear - $viewportStart) / yearRange) * timelineWidth;
      
      // Find nearby cluster within year threshold
      const nearbyCluster = clusters.find(cluster => 
        Math.abs(cluster.year - eventYear) <= yearThreshold && 
        cluster.events.length < 15
      );
      
      if (nearbyCluster) {
        // Add to existing cluster
        nearbyCluster.events.push(event);
        nearbyCluster.count = nearbyCluster.events.length;
        // Update position to average
        nearbyCluster.year = nearbyCluster.events.reduce((sum, e) => 
          sum + new Date(e.date).getFullYear(), 0) / nearbyCluster.events.length;
        nearbyCluster.x = padding + ((nearbyCluster.year - $viewportStart) / yearRange) * timelineWidth;
        nearbyCluster.radius = Math.min(maxEventRadius, baseEventRadius + Math.sqrt(nearbyCluster.count) * 1.2);
      } else {
        // Create new cluster
        const period = historicalPeriods.find(p => eventYear >= p.start && eventYear <= p.end);
        const baseY = canvasHeight * 0.5;
        
        // Sophisticated vertical positioning
        const eventHash = (event.title?.length || 0) + eventYear;
        const clusterOffset = (eventHash % 13 - 6) * 12;
        const periodOffset = period ? (historicalPeriods.indexOf(period) - 2) * 8 : 0;
        
        clusters.push({
          id: `cluster-${Date.now()}-${Math.random()}`,
          x,
          y: baseY + clusterOffset + periodOffset,
          year: eventYear,
          radius: baseEventRadius * $animationProgress,
          baseRadius: baseEventRadius,
          period,
          isCluster: true,
          count: 1,
          events: [event],
          title: event.title
        });
      }
    });
    
    return clusters;
  }
  
  function createFullDetailEvents(events) {
    // Full detail rendering for zoomed-in view
    const visibleEvents = events.filter(event => {
      const eventYear = new Date(event.date).getFullYear();
      return eventYear >= $viewportStart && eventYear <= $viewportEnd;
    });
    
    return visibleEvents.map((event, index) => {
      const eventYear = new Date(event.date).getFullYear();
      const x = padding + ((eventYear - $viewportStart) / yearRange) * timelineWidth;
      
      // Advanced organic positioning
      const period = historicalPeriods.find(p => eventYear >= p.start && eventYear <= p.end);
      const baseY = canvasHeight * 0.5;
      
      // Multi-layered positioning for natural distribution
      const titleHash = (event.title?.length || 0) % 17;
      const yearHash = eventYear % 19;
      const typeHash = (event.type?.charCodeAt(0) || 0) % 13;
      const combinedHash = (titleHash + yearHash + typeHash) % 23;
      
      const clusterOffset = (combinedHash - 11) * 14;
      const periodOffset = period ? (historicalPeriods.indexOf(period) - 2) * 12 : 0;
      const typeOffset = event.type === 'biography' ? -8 : event.type === 'article' ? 8 : 0;
      
      const y = Math.max(50, Math.min(canvasHeight - 50, 
        baseY + clusterOffset + periodOffset + typeOffset));
      
      // Dynamic radius based on content richness
      let radius = baseEventRadius;
      if (event.themes && event.themes.length > 5) radius += 2;
      else if (event.themes && event.themes.length > 2) radius += 1;
      if (event.type === 'biography') radius += 1.5;
      if (event.featured) radius += 2;
      
      // Smooth staggered animation
      const animDelay = (index % 50) * 0.005;
      const currentRadius = radius * Math.min(1, Math.max(0, $animationProgress - animDelay) * 2.5);
      
      return {
        ...event,
        x,
        y,
        year: eventYear,
        radius: currentRadius,
        baseRadius: radius,
        period,
        animDelay,
        hash: combinedHash,
        isFullDetail: true
      };
    });
  }
  
  onMount(() => {
    if (canvas) {
      ctx = canvas.getContext('2d');
      resizeCanvas();
      startAnimation();
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
  
  function startAnimation() {
    // Start the entrance animation
    animationProgress.set(1);
    requestAnimationFrame(draw);
  }
  
  function draw() {
    if (!ctx) return;
    
    // Clear canvas with subtle gradient background
    drawBackground();
    
    // Draw historical period bands
    drawHistoricalPeriods();
    
    // Draw modern timeline axis
    drawModernTimelineAxis();
    
    // Draw events with animations
    drawModernEvents();
    
    // Draw enhanced year labels
    drawModernYearLabels();
    
    // Continue animation if needed
    if ($animationProgress < 1 || hoveredEvent) {
      requestAnimationFrame(draw);
    }
  }
  
  function drawBackground() {
    // Create subtle gradient background
    const gradient = ctx.createLinearGradient(0, 0, 0, canvasHeight);
    gradient.addColorStop(0, '#f8f9fa');
    gradient.addColorStop(0.5, '#ffffff');
    gradient.addColorStop(1, '#f8f9fa');
    
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, canvasWidth, canvasHeight);
  }
  
  function drawHistoricalPeriods() {
    historicalPeriods.forEach(period => {
      if (period.end < $viewportStart || period.start > $viewportEnd) return;
      
      const startX = Math.max(padding, padding + ((period.start - $viewportStart) / yearRange) * timelineWidth);
      const endX = Math.min(canvasWidth - padding, padding + ((period.end - $viewportStart) / yearRange) * timelineWidth);
      const width = endX - startX;
      
      if (width <= 0) return;
      
      // Draw subtle period background band
      const periodGradient = ctx.createLinearGradient(startX, 0, startX, canvasHeight);
      periodGradient.addColorStop(0, period.gradient[0] + '15');
      periodGradient.addColorStop(0.5, period.gradient[1] + '25');
      periodGradient.addColorStop(1, period.gradient[0] + '15');
      
      ctx.fillStyle = periodGradient;
      ctx.fillRect(startX, 0, width, canvasHeight);
      
      // Period label at top
      if (width > 80) {
        ctx.save();
        ctx.fillStyle = period.colour + 'CC';
        ctx.font = 'bold 11px system-ui, -apple-system, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(period.name, startX + width / 2, 25);
        ctx.restore();
      }
    });
  }
  
  function drawModernTimelineAxis() {
    const centerY = canvasHeight / 2;
    
    // Main axis with gradient
    const axisGradient = ctx.createLinearGradient(padding, 0, canvasWidth - padding, 0);
    axisGradient.addColorStop(0, '#e9ecef');
    axisGradient.addColorStop(0.5, '#97212d');
    axisGradient.addColorStop(1, '#e9ecef');
    
    ctx.strokeStyle = axisGradient;
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.beginPath();
    ctx.moveTo(padding, centerY);
    ctx.lineTo(canvasWidth - padding, centerY);
    ctx.stroke();
    
    // Add subtle shadow
    ctx.shadowColor = 'rgba(0,0,0,0.1)';
    ctx.shadowBlur = 2;
    ctx.shadowOffsetY = 1;
    ctx.stroke();
    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;
    ctx.shadowOffsetY = 0;
  }
  
  function drawModernEvents() {
    // Draw density heat map for aggregated view
    if (processedEvents.some(e => e.isAggregate)) {
      drawDensityHeatMap();
    }
    
    // Draw connection lines first (behind events)
    ctx.globalAlpha = 0.25;
    processedEvents.forEach(event => {
      if (!event.isAggregate && Math.abs(event.y - canvasHeight / 2) > 8) {
        const gradient = ctx.createLinearGradient(event.x, event.y, event.x, canvasHeight / 2);
        gradient.addColorStop(0, event.period?.colour || '#97212d');
        gradient.addColorStop(1, 'transparent');
        
        ctx.strokeStyle = gradient;
        ctx.lineWidth = event.isCluster ? 2 : 1;
        ctx.beginPath();
        ctx.moveTo(event.x, event.y);
        ctx.lineTo(event.x, canvasHeight / 2);
        ctx.stroke();
      }
    });
    ctx.globalAlpha = 1;
    
    // Draw events with different styles based on type
    processedEvents.forEach(event => {
      const isHovered = hoveredEvent && (hoveredEvent.id === event.id || 
        (event.events && event.events.some(e => e.id === hoveredEvent.id)));
      
      if (event.isAggregate) {
        drawAggregatedEvent(event, isHovered);
      } else if (event.isCluster) {
        drawClusteredEvent(event, isHovered);
      } else {
        drawIndividualEvent(event, isHovered);
      }
    });
  }
  
  function drawDensityHeatMap() {
    // Create subtle density visualization behind aggregated events
    const heatMapHeight = 40;
    const centerY = canvasHeight / 2;
    
    processedEvents.forEach(event => {
      if (!event.isAggregate) return;
      
      const intensity = Math.min(0.3, event.count / 1000); // Scale intensity
      const gradient = ctx.createRadialGradient(
        event.x, centerY, 0,
        event.x, centerY, 60
      );
      
      gradient.addColorStop(0, event.period?.colour + Math.floor(intensity * 100).toString(16).padStart(2, '0'));
      gradient.addColorStop(1, 'transparent');
      
      ctx.fillStyle = gradient;
      ctx.fillRect(
        event.x - 30, 
        centerY - heatMapHeight / 2, 
        60, 
        heatMapHeight
      );
    });
  }
  
  function drawAggregatedEvent(event, isHovered) {
    const radius = isHovered ? event.baseRadius + 3 : event.radius;
    
    if (radius <= 0) return;
    
    // Pulsing effect for aggregated events
    const pulseRadius = radius + Math.sin(Date.now() * 0.003) * 2;
    
    // Outer glow
    if (isHovered) {
      ctx.shadowColor = event.period?.colour || '#97212d';
      ctx.shadowBlur = 20;
    }
    
    // Main aggregated circle with special gradient
    const gradient = ctx.createRadialGradient(
      event.x - radius * 0.2, event.y - radius * 0.2, 0,
      event.x, event.y, pulseRadius
    );
    gradient.addColorStop(0, '#ffffff');
    gradient.addColorStop(0.3, event.period?.colour || '#97212d');
    gradient.addColorStop(0.8, event.period?.gradient?.[1] || '#7a1b24');
    gradient.addColorStop(1, event.period?.colour + '80');
    
    ctx.fillStyle = gradient;
    ctx.beginPath();
    ctx.arc(event.x, event.y, pulseRadius, 0, 2 * Math.PI);
    ctx.fill();
    
    // Double border for aggregated events
    ctx.strokeStyle = event.period?.gradient?.[1] || '#7a1b24';
    ctx.lineWidth = 2;
    ctx.stroke();
    
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 1;
    ctx.stroke();
    
    // Count indicator for large aggregations
    if (event.count > 50) {
      ctx.fillStyle = '#ffffff';
      ctx.font = 'bold 10px system-ui';
      ctx.textAlign = 'center';
      ctx.fillText(
        event.count > 999 ? '999+' : event.count.toString(), 
        event.x, 
        event.y + 3
      );
    }
    
    // Reset shadow
    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;
  }
  
  function drawClusteredEvent(event, isHovered) {
    const radius = isHovered ? event.baseRadius + 2 : event.radius;
    
    if (radius <= 0) return;
    
    // Special styling for clusters
    if (isHovered) {
      ctx.shadowColor = event.period?.colour || '#97212d';
      ctx.shadowBlur = 12;
    }
    
    // Cluster gradient
    const gradient = ctx.createRadialGradient(
      event.x - radius * 0.3, event.y - radius * 0.3, 0,
      event.x, event.y, radius
    );
    gradient.addColorStop(0, '#ffffff');
    gradient.addColorStop(0.6, event.period?.colour || '#97212d');
    gradient.addColorStop(1, event.period?.gradient?.[1] || '#7a1b24');
    
    ctx.fillStyle = gradient;
    ctx.beginPath();
    ctx.arc(event.x, event.y, radius, 0, 2 * Math.PI);
    ctx.fill();
    
    // Cluster indicator (small dots around main circle for multiple events)
    if (event.count > 1) {
      const dotRadius = 1.5;
      const dots = Math.min(6, event.count - 1);
      
      for (let i = 0; i < dots; i++) {
        const angle = (i / dots) * Math.PI * 2;
        const dotX = event.x + Math.cos(angle) * (radius + 4);
        const dotY = event.y + Math.sin(angle) * (radius + 4);
        
        ctx.fillStyle = event.period?.colour || '#97212d';
        ctx.beginPath();
        ctx.arc(dotX, dotY, dotRadius, 0, 2 * Math.PI);
        ctx.fill();
      }
    }
    
    // Border
    ctx.strokeStyle = event.period?.gradient?.[1] || '#7a1b24';
    ctx.lineWidth = isHovered ? 2 : 1;
    ctx.stroke();
    
    // Reset shadow
    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;
  }
  
  function drawIndividualEvent(event, isHovered) {
    const radius = isHovered ? hoveredEventRadius : event.radius;
    
    if (radius <= 0) return;
    
    // Individual event styling (original high-quality rendering)
    if (isHovered) {
      ctx.shadowColor = event.period?.colour || '#97212d';
      ctx.shadowBlur = 15;
    }
    
    // Main event circle with gradient
    const eventGradient = ctx.createRadialGradient(
      event.x - radius * 0.3, event.y - radius * 0.3, 0,
      event.x, event.y, radius
    );
    eventGradient.addColorStop(0, '#ffffff');
    eventGradient.addColorStop(0.7, event.period?.colour || '#97212d');
    eventGradient.addColorStop(1, event.period?.gradient?.[1] || '#7a1b24');
    
    ctx.fillStyle = eventGradient;
    ctx.beginPath();
    ctx.arc(event.x, event.y, radius, 0, 2 * Math.PI);
    ctx.fill();
    
    // Border with period colour
    ctx.strokeStyle = event.period?.gradient?.[1] || '#7a1b24';
    ctx.lineWidth = isHovered ? 2 : 1;
    ctx.stroke();
    
    // Inner highlight
    ctx.fillStyle = 'rgba(255,255,255,0.4)';
    ctx.beginPath();
    ctx.arc(event.x - radius * 0.3, event.y - radius * 0.3, radius * 0.3, 0, 2 * Math.PI);
    ctx.fill();
    
    // Reset shadow
    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;
  }
  
  function drawModernYearLabels() {
    ctx.fillStyle = '#495057';
    ctx.font = '13px system-ui, -apple-system, sans-serif';
    ctx.textAlign = 'center';
    
    // Calculate appropriate year intervals
    const yearInterval = yearRange < 100 ? 10 : yearRange < 500 ? 25 : 50;
    const startYear = Math.ceil($viewportStart / yearInterval) * yearInterval;
    
    for (let year = startYear; year <= $viewportEnd; year += yearInterval) {
      const x = padding + ((year - $viewportStart) / yearRange) * timelineWidth;
      
      // Modern tick mark
      ctx.strokeStyle = '#adb5bd';
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';
      ctx.beginPath();
      ctx.moveTo(x, canvasHeight / 2 - 12);
      ctx.lineTo(x, canvasHeight / 2 + 12);
      ctx.stroke();
      
      // Year label with better typography
      ctx.fillStyle = '#495057';
      ctx.font = 'bold 13px system-ui, -apple-system, sans-serif';
      ctx.fillText(year.toString(), x, canvasHeight / 2 + 35);
      
      // Subtle year divider line
      if (yearInterval <= 25) {
        ctx.globalAlpha = 0.1;
        ctx.strokeStyle = '#6c757d';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, canvasHeight);
        ctx.stroke();
        ctx.globalAlpha = 1;
      }
    }
  }
  
  // Modern interaction handling with zoom and pan
  let isDragging = false;
  let lastMouseX = 0;
  let dragStartViewport = { start: 0, end: 0 };
  
  function handleMouseMove(e) {
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    // Handle dragging for pan
    if (isDragging) {
      const deltaX = e.clientX - lastMouseX;
      const yearDelta = (deltaX / timelineWidth) * yearRange;
      
      const newStart = Math.max(minYear, dragStartViewport.start - yearDelta);
      const newEnd = Math.min(maxYear, dragStartViewport.end - yearDelta);
      
      if (newEnd - newStart === yearRange) {
        viewportStart.set(newStart, { duration: 0 });
        viewportEnd.set(newEnd, { duration: 0 });
        requestAnimationFrame(draw);
      }
      return;
    }
    
    // Find hovered event with better detection
    const hovered = processedEvents.find(event => {
      const dx = x - event.x;
      const dy = y - event.y;
      return Math.sqrt(dx * dx + dy * dy) < (event.radius || baseEventRadius) + 8;
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
      
      requestAnimationFrame(draw);
    }
  }
  
  function handleMouseDown(e) {
    isDragging = true;
    lastMouseX = e.clientX;
    dragStartViewport = { start: $viewportStart, end: $viewportEnd };
    canvas.style.cursor = 'grabbing';
  }
  
  function handleMouseUp(e) {
    if (isDragging) {
      isDragging = false;
      canvas.style.cursor = hoveredEvent ? 'pointer' : 'grab';
    } else if (hoveredEvent) {
      dispatch('select', hoveredEvent);
    }
  }
  
  function handleWheel(e) {
    e.preventDefault();
    
    const rect = canvas.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const mouseRatio = (mouseX - padding) / timelineWidth;
    
    // Zoom factor
    const zoomFactor = e.deltaY > 0 ? 1.2 : 0.8;
    const newRange = yearRange * zoomFactor;
    
    // Constrain zoom
    if (newRange < 50) return; // Minimum zoom
    if (newRange > (maxYear - minYear)) {
      // Maximum zoom out
      viewportStart.set(minYear);
      viewportEnd.set(maxYear);
      return;
    }
    
    // Calculate new viewport centered on mouse position
    const centerYear = $viewportStart + mouseRatio * yearRange;
    const newStart = Math.max(minYear, centerYear - newRange / 2);
    const newEnd = Math.min(maxYear, newStart + newRange);
    
    viewportStart.set(newStart);
    viewportEnd.set(newEnd);
  }
  
  function zoomToTimeRange(startYear, endYear) {
    viewportStart.set(Math.max(minYear, startYear - 10));
    viewportEnd.set(Math.min(maxYear, endYear + 10));
  }
  
  function resetZoom() {
    viewportStart.set(minYear);
    viewportEnd.set(maxYear);
  }
  
  // Redraw when viewport changes
  $: if (ctx && events.length > 0 && ($viewportStart || $viewportEnd || $animationProgress)) {
    requestAnimationFrame(draw);
  }
  
  // Export zoom functions for external use
  export { zoomToTimeRange, resetZoom };
</script>

<svelte:window 
  on:resize={() => {
    if (canvas) {
      resizeCanvas();
      requestAnimationFrame(draw);
    }
  }} 
  on:mouseup={handleMouseUp}
/>

<div class="modern-timeline-container">
  <!-- Timeline Controls -->
  <div class="timeline-controls">
    <div class="zoom-controls">
      <button class="control-btn" on:click={resetZoom} title="Reset to full view">
        🔍 Reset Zoom
      </button>
      <div class="period-buttons">
        {#each historicalPeriods as period}
          <button 
            class="period-btn" 
            style="background: {period.colour}20; border-color: {period.colour}; color: {period.colour}"
            on:click={() => zoomToTimeRange(period.start, period.end)}
            title="Focus on {period.name} period"
          >
            {period.name}
          </button>
        {/each}
      </div>
    </div>
    <div class="timeline-info">
      <span class="viewport-range">
        Viewing: {Math.round($viewportStart)} - {Math.round($viewportEnd)}
      </span>
      <span class="event-count">
        {processedEvents.length} events visible
      </span>
    </div>
  </div>

  <!-- Modern Canvas Container -->
  <div class="canvas-wrapper">
    <canvas
      bind:this={canvas}
      on:mousemove={handleMouseMove}
      on:mousedown={handleMouseDown}
      on:mouseup={handleMouseUp}
      on:wheel={handleWheel}
      style="cursor: {isDragging ? 'grabbing' : hoveredEvent ? 'pointer' : 'grab'}"
    ></canvas>
    
    <!-- Enhanced Tooltip for Different Event Types -->
    {#if tooltip.visible && hoveredEvent}
      <div 
        class="modern-tooltip"
        class:aggregate-tooltip={hoveredEvent.isAggregate}
        class:cluster-tooltip={hoveredEvent.isCluster}
        style="left: {tooltip.x + 15}px; top: {tooltip.y - 15}px;"
      >
        <div class="tooltip-header" style="background: {hoveredEvent.period?.colour || '#97212d'}">
          <strong>{hoveredEvent.title}</strong>
          {#if hoveredEvent.isAggregate}
            <span class="tooltip-badge">📊 {hoveredEvent.count} Events</span>
          {:else if hoveredEvent.isCluster}
            <span class="tooltip-badge">🔗 {hoveredEvent.count} Grouped</span>
          {/if}
        </div>
        <div class="tooltip-body">
          {#if hoveredEvent.isAggregate}
            <!-- Aggregated Event Tooltip -->
            <div class="tooltip-year-range">{hoveredEvent.year}-{hoveredEvent.endYear || hoveredEvent.year + 10}</div>
            <div class="tooltip-density">Density: {Math.round(hoveredEvent.count / (hoveredEvent.endYear - hoveredEvent.year + 1))} events/year</div>
            {#if hoveredEvent.themes && hoveredEvent.themes.length > 0}
              <div class="tooltip-themes">
                <strong>Main themes:</strong> {hoveredEvent.themes.join(', ')}
              </div>
            {/if}
            <div class="tooltip-hint">🔍 Zoom in to see individual events</div>
          {:else if hoveredEvent.isCluster}
            <!-- Clustered Event Tooltip -->
            <div class="tooltip-year">{hoveredEvent.year}</div>
            <div class="tooltip-cluster-info">{hoveredEvent.count} events clustered together</div>
            {#if hoveredEvent.period}
              <div class="tooltip-period">{hoveredEvent.period.name} Period</div>
            {/if}
            <div class="tooltip-hint">🔍 Zoom in to separate events</div>
          {:else}
            <!-- Individual Event Tooltip -->
            <div class="tooltip-year">{new Date(hoveredEvent.date).getFullYear()}</div>
            {#if hoveredEvent.period}
              <div class="tooltip-period">{hoveredEvent.period.name} Period</div>
            {/if}
            {#if hoveredEvent.themes && hoveredEvent.themes.length > 0}
              <div class="tooltip-themes">
                {hoveredEvent.themes.slice(0, 2).join(', ')}
                {#if hoveredEvent.themes.length > 2}
                  <span class="more-themes">+{hoveredEvent.themes.length - 2} more</span>
                {/if}
              </div>
            {/if}
            <div class="tooltip-hint">👆 Click to view full details</div>
          {/if}
        </div>
      </div>
    {/if}
    
    <!-- Performance and Level-of-Detail Indicator -->
    <div class="performance-indicator">
      <div class="lod-indicator">
        {#if processedEvents.some(e => e.isAggregate)}
          <span class="lod-badge aggregate">📊 Aggregated View</span>
        {:else if processedEvents.some(e => e.isCluster)}
          <span class="lod-badge cluster">🔗 Clustered View</span>
        {:else}
          <span class="lod-badge full">🎯 Full Detail</span>
        {/if}
      </div>
      <div class="event-stats">
        <span class="stat">Showing {processedEvents.length}</span>
        <span class="stat">of {events.length} events</span>
      </div>
    </div>
    
    <!-- Pan/Zoom Instructions -->
    <div class="interaction-hints">
      <div class="hint">🖱️ Drag to pan</div>
      <div class="hint">🔍 Scroll to zoom</div>
      <div class="hint">👆 Click events for details</div>
    </div>
  </div>
</div>

<style>
  .modern-timeline-container {
    width: 100%;
    margin: 1.5rem 0;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    box-shadow: 
      0 8px 32px rgba(0,0,0,0.08),
      0 2px 8px rgba(0,0,0,0.04);
    overflow: hidden;
    border: 1px solid rgba(151,33,45,0.1);
  }
  
  .timeline-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #97212d 0%, #7a1b24 100%);
    color: white;
    gap: 1rem;
  }
  
  .zoom-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }
  
  .control-btn {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    backdrop-filter: blur(8px);
  }
  
  .control-btn:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  }
  
  .period-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  
  .period-btn {
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    border: 1px solid;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    background: rgba(255,255,255,0.1);
  }
  
  .period-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    background: rgba(255,255,255,0.2);
  }
  
  .timeline-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
    font-size: 0.85rem;
  }
  
  .viewport-range {
    font-weight: 600;
    opacity: 0.9;
  }
  
  .event-count {
    opacity: 0.7;
    font-size: 0.75rem;
  }
  
  .canvas-wrapper {
    position: relative;
    height: 500px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 50%, #f8f9fa 100%);
  }
  
  canvas {
    display: block;
    width: 100%;
    height: 100%;
    user-select: none;
  }
  
  .modern-tooltip {
    position: fixed;
    background: white;
    border-radius: 12px;
    box-shadow: 
      0 12px 48px rgba(0,0,0,0.15),
      0 4px 16px rgba(0,0,0,0.08);
    pointer-events: none;
    z-index: 1000;
    max-width: 280px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.08);
    transform: translateY(-5px);
    animation: tooltipIn 0.2s ease-out;
  }
  
  @keyframes tooltipIn {
    from {
      opacity: 0;
      transform: translateY(-15px) scale(0.95);
    }
    to {
      opacity: 1;
      transform: translateY(-5px) scale(1);
    }
  }
  
  .tooltip-header {
    padding: 0.75rem 1rem;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    background: linear-gradient(135deg, var(--period-colour, #97212d) 0%, #7a1b24 100%);
  }
  
  .tooltip-body {
    padding: 1rem;
    background: white;
  }
  
  .tooltip-year {
    font-size: 1.1rem;
    font-weight: 700;
    color: #495057;
    margin-bottom: 0.5rem;
  }
  
  .tooltip-period {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .tooltip-themes {
    font-size: 0.85rem;
    color: #495057;
    margin-bottom: 0.75rem;
    line-height: 1.4;
  }
  
  .more-themes {
    color: #6c757d;
    font-style: italic;
  }
  
  .tooltip-hint {
    font-size: 0.75rem;
    color: #97212d;
    font-weight: 500;
    opacity: 0.8;
  }
  
  .tooltip-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-left: 0.5rem;
  }
  
  .tooltip-year-range {
    font-size: 1.2rem;
    font-weight: 700;
    color: #495057;
    margin-bottom: 0.5rem;
  }
  
  .tooltip-density {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
    font-style: italic;
  }
  
  .tooltip-cluster-info {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
    font-weight: 500;
  }
  
  .aggregate-tooltip {
    border-left: 4px solid #ffc107;
  }
  
  .cluster-tooltip {
    border-left: 4px solid #17a2b8;
  }
  
  .performance-indicator {
    position: absolute;
    top: 1rem;
    left: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    opacity: 0.8;
    transition: opacity 0.2s ease;
  }
  
  .performance-indicator:hover {
    opacity: 1;
  }
  
  .lod-indicator {
    display: flex;
    align-items: center;
  }
  
  .lod-badge {
    background: rgba(255,255,255,0.9);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(0,0,0,0.1);
  }
  
  .lod-badge.aggregate {
    background: linear-gradient(135deg, #ffc107, #ffca2c);
    color: #212529;
  }
  
  .lod-badge.cluster {
    background: linear-gradient(135deg, #17a2b8, #20c997);
    color: white;
  }
  
  .lod-badge.full {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
  }
  
  .event-stats {
    display: flex;
    gap: 0.5rem;
    font-size: 0.65rem;
  }
  
  .stat {
    background: rgba(255,255,255,0.8);
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    color: #495057;
    font-weight: 500;
    backdrop-filter: blur(4px);
  }
  
  .interaction-hints {
    position: absolute;
    bottom: 1rem;
    right: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    opacity: 0.6;
    transition: opacity 0.2s ease;
  }
  
  .interaction-hints:hover {
    opacity: 1;
  }
  
  .hint {
    background: rgba(255,255,255,0.9);
    padding: 0.3rem 0.6rem;
    border-radius: 12px;
    font-size: 0.7rem;
    color: #495057;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(0,0,0,0.05);
    font-weight: 500;
  }
  
  /* Mobile responsiveness */
  @media (max-width: 768px) {
    .timeline-controls {
      flex-direction: column;
      gap: 1rem;
      align-items: stretch;
    }
    
    .timeline-info {
      align-items: center;
      text-align: center;
    }
    
    .zoom-controls {
      justify-content: center;
    }
    
    .period-buttons {
      justify-content: center;
    }
    
    .period-btn {
      font-size: 0.7rem;
      padding: 0.3rem 0.6rem;
    }
    
    .canvas-wrapper {
      height: 400px;
    }
    
    .interaction-hints {
      display: none;
    }
    
    .modern-tooltip {
      max-width: 250px;
      font-size: 0.85rem;
    }
  }
  
  @media (max-width: 480px) {
    .modern-timeline-container {
      margin: 1rem 0;
      border-radius: 12px;
    }
    
    .timeline-controls {
      padding: 0.75rem 1rem;
    }
    
    .canvas-wrapper {
      height: 350px;
    }
    
    .period-buttons {
      grid-template-columns: repeat(2, 1fr);
      display: grid;
      gap: 0.4rem;
    }
    
    .period-btn {
      font-size: 0.65rem;
      padding: 0.25rem 0.5rem;
    }
  }
</style>