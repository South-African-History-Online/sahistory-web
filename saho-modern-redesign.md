# SAHO Modern Style & Functionality Redesign Plan

## 1. Modern Design System Implementation

### Color Palette Evolution
```scss
// themes/custom/saho/scss/_variables.scss

// Primary Colors - Inspired by South African flag with modern interpretation
$primary-colors: (
  'emerald': #990000,      // Modern green
  'amber': #f59e0b,        // Vibrant gold
  'slate': #1e293b,        // Deep navy
  'crimson': #990000,      // Bold red
);

// Neutral Palette - Modern grays with warm undertones
$neutral-colors: (
  'surface': #ffffff,
  'surface-alt': #fafafa,
  'muted': #f8fafc,
  'border': #e2e8f0,
  'text-primary': #0f172a,
  'text-secondary': #64748b,
  'text-muted': #94a3b8,
);

// Dark Mode Colors
$dark-colors: (
  'bg-primary': #0f172a,
  'bg-secondary': #1e293b,
  'bg-tertiary': #334155,
  'surface': #1e293b,
  'surface-hover': #334155,
  'border': #475569,
  'text-primary': #f8fafc,
  'text-secondary': #cbd5e1,
);

// Semantic Colors
$semantic-colors: (
  'success': #22c55e,
  'warning': #eab308,
  'error': #ef4444,
  'info': #3b82f6,
);
```

### Typography System
```scss
// Modern Font Stack
@font-face {
  font-family: 'Inter';
  src: url('/themes/custom/saho/fonts/Inter-var.woff2') format('woff2-variations');
  font-weight: 100 900;
  font-display: swap;
}

// Typography Scale - Using fluid typography
$type-scale: (
  'display-xl': clamp(3rem, 5vw + 1rem, 5rem),
  'display': clamp(2.5rem, 4vw + 1rem, 4rem),
  'h1': clamp(2rem, 3vw + 1rem, 3rem),
  'h2': clamp(1.5rem, 2vw + 1rem, 2.25rem),
  'h3': clamp(1.25rem, 1.5vw + 0.5rem, 1.875rem),
  'h4': clamp(1.125rem, 1vw + 0.5rem, 1.5rem),
  'body-lg': 1.125rem,
  'body': 1rem,
  'body-sm': 0.875rem,
  'caption': 0.75rem,
);

// Font Weights
$font-weights: (
  'light': 300,
  'regular': 400,
  'medium': 500,
  'semibold': 600,
  'bold': 700,
  'black': 900,
);
```

### Modern Component Library

#### 1. Hero Section with Parallax
```jsx
// themes/custom/saho/components/hero/ParallaxHero.jsx
import { useEffect, useRef } from 'react';
import { motion, useScroll, useTransform } from 'framer-motion';

const ParallaxHero = ({ title, subtitle, backgroundImage, ctaText, ctaLink }) => {
  const ref = useRef(null);
  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ["start start", "end start"]
  });

  const y = useTransform(scrollYProgress, [0, 1], ["0%", "50%"]);
  const opacity = useTransform(scrollYProgress, [0, 1], [1, 0]);

  return (
    <section ref={ref} className="relative h-screen overflow-hidden">
      <motion.div 
        className="absolute inset-0 z-0"
        style={{ y }}
      >
        <img 
          src={backgroundImage} 
          alt="" 
          className="w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-b from-black/50 via-black/30 to-black/50" />
      </motion.div>

      <motion.div 
        className="relative z-10 h-full flex items-center justify-center text-center px-4"
        style={{ opacity }}
      >
        <div className="max-w-5xl mx-auto">
          <motion.h1 
            className="text-display-xl font-black text-white mb-6"
            initial={{ y: 30, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ duration: 0.8, ease: "easeOut" }}
          >
            {title}
          </motion.h1>
          
          <motion.p 
            className="text-h3 text-white/90 mb-8 max-w-3xl mx-auto"
            initial={{ y: 30, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ duration: 0.8, delay: 0.2, ease: "easeOut" }}
          >
            {subtitle}
          </motion.p>

          <motion.div
            initial={{ y: 30, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ duration: 0.8, delay: 0.4, ease: "easeOut" }}
          >
            <a 
              href={ctaLink}
              className="inline-flex items-center gap-3 px-8 py-4 bg-emerald-500 text-white font-semibold rounded-full hover:bg-emerald-600 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-emerald-500/25"
            >
              {ctaText}
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </a>
          </motion.div>
        </div>
      </motion.div>

      {/* Scroll Indicator */}
      <motion.div 
        className="absolute bottom-8 left-1/2 -translate-x-1/2"
        animate={{ y: [0, 10, 0] }}
        transition={{ duration: 1.5, repeat: Infinity }}
      >
        <div className="w-6 h-10 border-2 border-white/50 rounded-full flex justify-center">
          <div className="w-1 h-3 bg-white/50 rounded-full mt-2" />
        </div>
      </motion.div>
    </section>
  );
};
```

#### 2. Interactive Timeline with 3D Elements
```jsx
// themes/custom/saho/components/timeline/Timeline3D.jsx
import * as THREE from 'three';
import { Canvas, useFrame } from '@react-three/fiber';
import { Text, ScrollControls, Scroll, useScroll } from '@react-three/drei';

const TimelineEvent = ({ position, year, title, description, index }) => {
  const scroll = useScroll();
  const meshRef = useRef();
  
  useFrame(() => {
    const offset = scroll.offset;
    meshRef.current.rotation.y = offset * Math.PI * 2;
    meshRef.current.position.z = Math.sin(offset * Math.PI * 2 + index) * 2;
  });

  return (
    <group position={position}>
      <mesh ref={meshRef}>
        <sphereGeometry args={[0.5, 32, 32]} />
        <meshStandardMaterial 
          color={new THREE.Color(`hsl(${index * 30}, 70%, 50%)`)} 
          metalness={0.8}
          roughness={0.2}
        />
      </mesh>
      <Text
        position={[0, 1.5, 0]}
        fontSize={0.5}
        color="white"
        anchorX="center"
        anchorY="middle"
      >
        {year}
      </Text>
      <Text
        position={[0, 1, 0]}
        fontSize={0.3}
        color="white"
        anchorX="center"
        anchorY="middle"
        maxWidth={3}
      >
        {title}
      </Text>
    </group>
  );
};

const Timeline3D = ({ events }) => {
  return (
    <div className="h-screen bg-gradient-to-b from-slate-900 to-slate-800">
      <Canvas camera={{ position: [0, 0, 10], fov: 60 }}>
        <ambientLight intensity={0.5} />
        <pointLight position={[10, 10, 10]} intensity={1} />
        <ScrollControls pages={events.length / 3} damping={0.1}>
          <Scroll>
            {events.map((event, index) => (
              <TimelineEvent
                key={event.id}
                position={[0, -index * 2, 0]}
                year={event.year}
                title={event.title}
                description={event.description}
                index={index}
              />
            ))}
          </Scroll>
        </ScrollControls>
      </Canvas>
    </div>
  );
};
```

#### 3. Glassmorphism Card Components
```scss
// themes/custom/saho/scss/components/_glass-card.scss
.glass-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 1rem;
  box-shadow: 
    0 8px 32px 0 rgba(31, 38, 135, 0.15),
    inset 0 0 0 1px rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;

  &:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
    box-shadow: 
      0 12px 40px 0 rgba(31, 38, 135, 0.25),
      inset 0 0 0 1px rgba(255, 255, 255, 0.15);
  }

  &--dark {
    background: rgba(0, 0, 0, 0.3);
    border-color: rgba(255, 255, 255, 0.1);
    
    &:hover {
      background: rgba(0, 0, 0, 0.4);
    }
  }

  &__content {
    padding: 2rem;
    position: relative;
    z-index: 1;
  }

  &__shimmer {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
      105deg,
      transparent 40%,
      rgba(255, 255, 255, 0.2) 50%,
      transparent 60%
    );
    animation: shimmer 2s infinite;
  }
}

@keyframes shimmer {
  to {
    left: 100%;
  }
}
```

### 2. Modern Navigation System

#### Magnetic Navigation Menu
```jsx
// themes/custom/saho/components/navigation/MagneticNav.jsx
import { useRef, useState } from 'react';
import { motion, useMotionValue, useSpring } from 'framer-motion';

const MagneticNavItem = ({ children, href }) => {
  const ref = useRef(null);
  const [isHovered, setIsHovered] = useState(false);
  
  const x = useMotionValue(0);
  const y = useMotionValue(0);
  
  const springX = useSpring(x, { stiffness: 350, damping: 20 });
  const springY = useSpring(y, { stiffness: 350, damping: 20 });

  const handleMouseMove = (e) => {
    if (!ref.current) return;
    
    const rect = ref.current.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;
    
    const distanceX = e.clientX - centerX;
    const distanceY = e.clientY - centerY;
    
    x.set(distanceX * 0.1);
    y.set(distanceY * 0.1);
  };

  const handleMouseLeave = () => {
    x.set(0);
    y.set(0);
    setIsHovered(false);
  };

  return (
    <motion.a
      ref={ref}
      href={href}
      className="relative px-6 py-3 text-white font-medium"
      onMouseMove={handleMouseMove}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={handleMouseLeave}
      style={{ x: springX, y: springY }}
    >
      <span className="relative z-10">{children}</span>
      <motion.div
        className="absolute inset-0 bg-white/10 rounded-full"
        initial={{ scale: 0, opacity: 0 }}
        animate={{ 
          scale: isHovered ? 1 : 0, 
          opacity: isHovered ? 1 : 0 
        }}
        transition={{ duration: 0.3 }}
      />
    </motion.a>
  );
};
```

#### Sticky Header with Blur Effect
```scss
// themes/custom/saho/scss/components/_header.scss
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 50;
  transition: all 0.3s ease;

  &__wrapper {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
  }

  &--scrolled {
    .header__wrapper {
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    }
  }

  &--dark {
    .header__wrapper {
      background: rgba(15, 23, 42, 0.8);
      border-bottom-color: rgba(255, 255, 255, 0.1);
    }
  }
}
```

### 3. Advanced Search Interface

#### AI-Powered Search with Voice Input
```jsx
// themes/custom/saho/components/search/AISearch.jsx
import { useState, useRef, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';

const AISearch = () => {
  const [query, setQuery] = useState('');
  const [isListening, setIsListening] = useState(false);
  const [suggestions, setSuggestions] = useState([]);
  const [searchMode, setSearchMode] = useState('text'); // text, voice, image
  const [isProcessing, setIsProcessing] = useState(false);
  
  const recognitionRef = useRef(null);

  useEffect(() => {
    if ('webkitSpeechRecognition' in window) {
      recognitionRef.current = new webkitSpeechRecognition();
      recognitionRef.current.continuous = false;
      recognitionRef.current.interimResults = true;
      
      recognitionRef.current.onresult = (event) => {
        const transcript = Array.from(event.results)
          .map(result => result[0])
          .map(result => result.transcript)
          .join('');
        
        setQuery(transcript);
      };
      
      recognitionRef.current.onend = () => {
        setIsListening(false);
      };
    }
  }, []);

  const handleVoiceSearch = () => {
    if (isListening) {
      recognitionRef.current.stop();
    } else {
      recognitionRef.current.start();
      setIsListening(true);
    }
  };

  const handleImageUpload = async (file) => {
    setIsProcessing(true);
    // Process image with AI for visual search
    const formData = new FormData();
    formData.append('image', file);
    
    try {
      const response = await fetch('/api/ai/image-search', {
        method: 'POST',
        body: formData
      });
      const data = await response.json();
      setSuggestions(data.results);
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <div className="relative max-w-4xl mx-auto">
      <div className="glass-card glass-card--dark">
        <div className="flex items-center gap-4 p-6">
          {/* Search Mode Toggles */}
          <div className="flex gap-2">
            <motion.button
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
              onClick={() => setSearchMode('text')}
              className={`p-3 rounded-xl transition-all ${
                searchMode === 'text' 
                  ? 'bg-emerald-500 text-white' 
                  : 'bg-white/10 text-white/70 hover:bg-white/20'
              }`}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </motion.button>
            
            <motion.button
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
              onClick={() => setSearchMode('voice')}
              className={`p-3 rounded-xl transition-all ${
                searchMode === 'voice' 
                  ? 'bg-emerald-500 text-white' 
                  : 'bg-white/10 text-white/70 hover:bg-white/20'
              }`}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
              </svg>
            </motion.button>
            
            <motion.button
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
              onClick={() => setSearchMode('image')}
              className={`p-3 rounded-xl transition-all ${
                searchMode === 'image' 
                  ? 'bg-emerald-500 text-white' 
                  : 'bg-white/10 text-white/70 hover:bg-white/20'
              }`}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </motion.button>
          </div>

          {/* Search Input */}
          <div className="flex-1 relative">
            {searchMode === 'text' && (
              <input
                type="text"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Search South African history..."
                className="w-full px-6 py-3 bg-white/10 border border-white/20 rounded-full text-white placeholder-white/50 focus:outline-none focus:border-emerald-500 transition-all"
              />
            )}
            
            {searchMode === 'voice' && (
              <div className="flex items-center justify-center py-8">
                <motion.button
                  animate={isListening ? { scale: [1, 1.2, 1] } : {}}
                  transition={{ duration: 1, repeat: Infinity }}
                  onClick={handleVoiceSearch}
                  className={`w-20 h-20 rounded-full flex items-center justify-center ${
                    isListening ? 'bg-red-500' : 'bg-emerald-500'
                  }`}
                >
                  <svg className="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  </svg>
                </motion.button>
                {isListening && (
                  <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    className="ml-4 text-white"
                  >
                    Listening...
                  </motion.div>
                )}
              </div>
            )}
            
            {searchMode === 'image' && (
              <div className="py-8">
                <label className="flex flex-col items-center justify-center w-full h-32 border-2 border-white/20 border-dashed rounded-lg cursor-pointer hover:border-emerald-500 transition-all">
                  <div className="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg className="w-10 h-10 mb-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <p className="mb-2 text-sm text-white/50">
                      <span className="font-semibold">Click to upload</span> or drag and drop
                    </p>
                  </div>
                  <input 
                    type="file" 
                    className="hidden" 
                    accept="image/*"
                    onChange={(e) => handleImageUpload(e.target.files[0])}
                  />
                </label>
              </div>
            )}
          </div>

          {/* AI Assistant Avatar */}
          <motion.div
            animate={{ rotate: isProcessing ? 360 : 0 }}
            transition={{ duration: 2, repeat: isProcessing ? Infinity : 0 }}
            className="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-400 to-cyan-500 flex items-center justify-center"
          >
            <span className="text-white font-bold">AI</span>
          </motion.div>
        </div>

        {/* Smart Suggestions */}
        <AnimatePresence>
          {suggestions.length > 0 && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className="border-t border-white/10 p-4"
            >
              <div className="grid gap-2">
                {suggestions.map((suggestion, index) => (
                  <motion.a
                    key={index}
                    href={suggestion.url}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.1 }}
                    className="flex items-center gap-4 p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-all"
                  >
                    <div className="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center">
                      {suggestion.icon}
                    </div>
                    <div className="flex-1">
                      <h4 className="text-white font-medium">{suggestion.title}</h4>
                      <p className="text-white/60 text-sm">{suggestion.description}</p>
                    </div>
                    <div className="text-emerald-400 text-sm">
                      {suggestion.relevance}% match
                    </div>
                  </motion.a>
                ))}
              </div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </div>
  );
};
```

### 4. Interactive Data Visualizations

#### Historical Data Dashboard
```jsx
// themes/custom/saho/components/visualizations/HistoricalDashboard.jsx
import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Line, Bar, Doughnut } from 'react-chartjs-2';

const HistoricalDashboard = () => {
  const [selectedPeriod, setSelectedPeriod] = useState('all');
  const [animatedStats, setAnimatedStats] = useState({
    events: 0,
    figures: 0,
    documents: 0,
    images: 0
  });

  useEffect(() => {
    // Animate numbers
    const duration = 2000;
    const steps = 60;
    const targets = { events: 1247, figures: 892, documents: 3456, images: 7891 };
    
    let current = 0;
    const timer = setInterval(() => {
      current += 1;
      const progress = current / steps;
      
      setAnimatedStats({
        events: Math.floor(targets.events * progress),
        figures: Math.floor(targets.figures * progress),
        documents: Math.floor(targets.documents * progress),
        images: Math.floor(targets.images * progress)
      });
      
      if (current >= steps) clearInterval(timer);
    }, duration / steps);
    
    return () => clearInterval(timer);
  }, []);

  const statsCards = [
    { 
      label: 'Historical Events', 
      value: animatedStats.events,
      icon: '📅',
      color: 'from-blue-500 to-cyan-500'
    },
    { 
      label: 'Historical Figures', 
      value: animatedStats.figures,
      icon: '👤',
      color: 'from-purple-500 to-pink-500'
    },
    { 
      label: 'Documents', 
      value: animatedStats.documents,
      icon: '📄',
      color: 'from-green-500 to-emerald-500'
    },
    { 
      label: 'Images', 
      value: animatedStats.images,
      icon: '🖼️',
      color: 'from-orange-500 to-red-500'
    }
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <motion.div
          initial={{ y: -20, opacity: 0 }}
          animate={{ y: 0, opacity: 1 }}
          className="mb-8"
        >
          <h1 className="text-4xl font-bold text-white mb-2">Historical Data Explorer</h1>
          <p className="text-white/60">Discover patterns and insights from South African history</p>
        </motion.div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {statsCards.map((stat, index) => (
            <motion.div
              key={stat.label}
              initial={{ scale: 0, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              transition={{ delay: index * 0.1, type: "spring", stiffness: 100 }}
              whileHover={{ scale: 1.05 }}
              className="relative overflow-hidden rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 p-6"
            >
              <div className={`absolute top-0 right-0 w-32 h-32 bg-gradient-to-br ${stat.color} opacity-10 blur-3xl`} />
              <div className="relative z-10">
                <div className="text-4xl mb-3">{stat.icon}</div>
                <div className="text-3xl font-bold text-white mb-1">
                  {stat.value.toLocaleString()}
                </div>
                <div className="text-white/60 text-sm">{stat.label}</div>
              </div>
            </motion.div>
          ))}
        </div>

        {/* Interactive Charts */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <motion.div
            initial={{ x: -50, opacity: 0 }}
            animate={{ x: 0, opacity: 1 }}
            transition={{ delay: 0.5 }}
            className="glass-card p-6"
          >
            <h3 className="text-xl font-semibold text-white mb-4">Events Timeline</h3>
            <Line
              data={{
                labels: ['1600s', '1700s', '1800s', '1900s', '2000s'],
                datasets: [{
                  label: 'Historical Events',
                  data: [12, 45, 89, 234, 156],
                  borderColor: 'rgb(16, 185, 129)',
                  backgroundColor: 'rgba(16, 185, 129, 0.1)',
                  tension: 0.4
                }]
              }}
              options={{
                responsive: true,
                plugins: {
                  legend: { display: false }
                },
                scales: {
                  x: { 
                    grid: { color: 'rgba(255, 255, 255, 0.1)' },
                    ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                  },
                  y: { 
                    grid: { color: 'rgba(255, 255, 255, 0.1)' },
                    ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                  }
                }
              }}
            />
          </motion.div>

          <motion.div
            initial={{ x: 50, opacity: 0 }}
            animate={{ x: 0, opacity: 1 }}
            transition={{ delay: 0.6 }}
            className="glass-card p-6"
          >
            <h3 className="text-xl font-semibold text-white mb-4">Content Distribution</h3>
            <Doughnut
              data={{
                labels: ['Political', 'Cultural', 'Economic', 'Social', 'Military'],
                datasets: [{
                  data: [30, 25, 20, 15, 10],
                  backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(251, 146, 60, 0.8)',
                    'rgba(147, 51, 234, 0.8)'
                  ]
                }]
              }}
              options={{
                responsive: true,
                plugins: {
                  legend: {
                    position: 'bottom',
                    labels: { color: 'rgba(255, 255, 255, 0.7)' }
                  }
                }
              }}
            />
          </motion.div>
        </div>
      </div>
    </div>
  );
};
```

### 5. Modern Content Display

#### Masonry Grid with Hover Effects
```jsx
// themes/custom/saho/components/content/MasonryGrid.jsx
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import Masonry from 'react-masonry-css';

const ContentCard = ({ item, index }) => {
  const [isHovered, setIsHovered] = useState(false);
  
  return (
    <motion.article
      layout
      initial={{ opacity: 0, y: 50 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.1 }}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      className="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-800 shadow-xl mb-6 group"
    >
      {item.image && (
        <div className="relative h-64 overflow-hidden">
          <motion.img
            src={item.image}
            alt={item.title}
            className="w-full h-full object-cover"
            animate={{ scale: isHovered ? 1.1 : 1 }}
            transition={{ duration: 0.6 }}
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
        </div>
      )}
      
      <div className="p-6">
        <div className="flex items-center gap-2 mb-3">
          <span className="px-3 py-1 text-xs font-medium bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded-full">
            {item.category}
          </span>
          <span className="text-sm text-gray-500 dark:text-gray-400">
            {item.date}
          </span>
        </div>
        
        <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
          {item.title}
        </h3>
        
        <p className="text-gray-600 dark:text-gray-300 mb-4 line-clamp-3">
          {item.excerpt}
        </p>
        
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <img
              src={item.author.avatar}
              alt={item.author.name}
              className="w-8 h-8 rounded-full"
            />
            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
              {item.author.name}
            </span>
          </div>
          
          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            className="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-medium"
          >
            Read More
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </motion.button>
        </div>
      </div>
      
      {/* Floating Action Button */}
      <AnimatePresence>
        {isHovered && (
          <motion.div
            initial={{ scale: 0, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            exit={{ scale: 0, opacity: 0 }}
            className="absolute top-4 right-4 flex gap-2"
          >
            <button className="w-10 h-10 rounded-full bg-white/90 backdrop-blur-sm shadow-lg flex items-center justify-center hover:bg-white transition-colors">
              <svg className="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
            </button>
            <button className="w-10 h-10 rounded-full bg-white/90 backdrop-blur-sm shadow-lg flex items-center justify-center hover:bg-white transition-colors">
              <svg className="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.032 4.026a9.001 9.001 0 010-5.684m-9.032 5.684a9.001 9.001 0 01-5.684 0m14.716 0a3 3 0 00-5.684 0" />
              </svg>
            </button>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.article>
  );
};

const MasonryGrid = ({ items }) => {
  const breakpointColumns = {
    default: 3,
    1100: 2,
    700: 1
  };

  return (
    <Masonry
      breakpointCols={breakpointColumns}
      className="flex -ml-6 w-auto"
      columnClassName="pl-6 bg-clip-padding"
    >
      {items.map((item, index) => (
        <ContentCard key={item.id} item={item} index={index} />
      ))}
    </Masonry>
  );
};
```

### 6. Modern User Interaction Patterns

#### Gesture-Based Mobile Navigation
```jsx
// themes/custom/saho/components/mobile/GestureNav.jsx
import { useRef } from 'react';
import { motion, useAnimation, PanInfo } from 'framer-motion';

const GestureNav = ({ children, onSwipeUp, onSwipeDown }) => {
  const controls = useAnimation();
  const containerRef = useRef(null);

  const handleDragEnd = (event: MouseEvent | TouchEvent | PointerEvent, info: PanInfo) => {
    const threshold = 100;
    
    if (info.offset.y < -threshold && onSwipeUp) {
      onSwipeUp();
      controls.start({ y: '-100%' });
    } else if (info.offset.y > threshold && onSwipeDown) {
      onSwipeDown();
      controls.start({ y: '100%' });
    } else {
      controls.start({ y: 0 });
    }
  };

  return (
    <motion.div
      ref={containerRef}
      drag="y"
      dragConstraints={{ top: 0, bottom: 0 }}
      dragElastic={0.2}
      onDragEnd={handleDragEnd}
      animate={controls}
      className="h-full w-full"
    >
      {children}
    </motion.div>
  );
};
```

#### Floating Action Menu
```scss
// themes/custom/saho/scss/components/_fab.scss
.fab {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  z-index: 40;

  &__button {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, #990000 0%, #990000 100%);
    box-shadow: 
      0 4px 20px rgba(16, 185, 129, 0.4),
      0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    &:hover {
      transform: scale(1.1) rotate(90deg);
      box-shadow: 
        0 6px 30px rgba(16, 185, 129, 0.5),
        0 3px 8px rgba(0, 0, 0, 0.15);
    }

    &:active {
      transform: scale(0.95);
    }
  }

  &__menu {
    position: absolute;
    bottom: 4.5rem;
    right: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    opacity: 0;
    transform: scale(0.8) translateY(1rem);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;

    &--open {
      opacity: 1;
      transform: scale(1) translateY(0);
      pointer-events: auto;
    }
  }

  &__item {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    background: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
      transform: scale(1.1);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    @for $i from 1 through 5 {
      &:nth-child(#{$i}) {
        transition-delay: #{$i * 0.05}s;
      }
    }
  }
}
```

### 7. Dark Mode Implementation

#### Advanced Theme Switcher
```jsx
// themes/custom/saho/components/theme/ThemeSwitcher.jsx
import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';

const ThemeSwitcher = () => {
  const [theme, setTheme] = useState('light');
  const [isOpen, setIsOpen] = useState(false);

  const themes = [
    { id: 'light', name: 'Light', icon: '☀️', colors: ['#ffffff', '#f3f4f6'] },
    { id: 'dark', name: 'Dark', icon: '🌙', colors: ['#0f172a', '#1e293b'] },
    { id: 'sepia', name: 'Sepia', icon: '📜', colors: ['#f4f1e8', '#e6dcc8'] },
    { id: 'high-contrast', name: 'High Contrast', icon: '🔲', colors: ['#000000', '#ffffff'] }
  ];

  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme);
  }, [theme]);

  return (
    <div className="relative">
      <motion.button
        whileHover={{ scale: 1.05 }}
        whileTap={{ scale: 0.95 }}
        onClick={() => setIsOpen(!isOpen)}
        className="p-3 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
      >
        <span className="text-2xl">{themes.find(t => t.id === theme)?.icon}</span>
      </motion.button>

      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, scale: 0.9, y: -10 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.9, y: -10 }}
            className="absolute top-full mt-2 right-0 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 p-2 min-w-[200px]"
          >
            {themes.map((themeOption) => (
              <motion.button
                key={themeOption.id}
                whileHover={{ x: 4 }}
                onClick={() => {
                  setTheme(themeOption.id);
                  setIsOpen(false);
                }}
                className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors ${
                  theme === themeOption.id
                    ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300'
                    : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                }`}
              >
                <span className="text-xl">{themeOption.icon}</span>
                <span className="font-medium">{themeOption.name}</span>
                <div className="ml-auto flex gap-1">
                  {themeOption.colors.map((color, index) => (
                    <div
                      key={index}
                      className="w-4 h-4 rounded-full border border-gray-300"
                      style={{ backgroundColor: color }}
                    />
                  ))}
                </div>
              </motion.button>
            ))}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};
```

### 8. Modern Loading States

#### Skeleton Screens with Shimmer
```scss
// themes/custom/saho/scss/components/_skeleton.scss
.skeleton {
  position: relative;
  overflow: hidden;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;

  &--dark {
    background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%);
  }

  &--text {
    height: 1rem;
    border-radius: 0.25rem;
    margin-bottom: 0.5rem;

    &:last-child {
      width: 80%;
    }
  }

  &--title {
    height: 2rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
  }

  &--image {
    height: 12rem;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
  }

  &--avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
  }

  &--button {
    height: 2.5rem;
    width: 8rem;
    border-radius: 1.25rem;
  }
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

// Content loader component
.content-loader {
  &__card {
    @apply bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm;

    .skeleton {
      @apply bg-gray-200 dark:bg-gray-700;
    }
  }

  &__list {
    @apply space-y-4;
  }

  &__grid {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
  }
}
```

### 9. Micro-interactions and Animations

#### Hover Effects Library
```scss
// themes/custom/saho/scss/components/_hover-effects.scss
// Magnetic hover effect
.hover-magnetic {
  position: relative;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);

  &:hover {
    transform: translate(var(--mouse-x), var(--mouse-y));
  }
}

// Glitch effect
.hover-glitch {
  position: relative;

  &:hover {
    animation: glitch 0.3s ease-in-out;

    &::before,
    &::after {
      content: attr(data-text);
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    &::before {
      animation: glitch-1 0.3s ease-in-out;
      color: #990000;
      z-index: -1;
    }

    &::after {
      animation: glitch-2 0.3s ease-in-out;
      color: #dc2626;
      z-index: -2;
    }
  }
}

@keyframes glitch {
  0%, 100% {
    transform: translate(0);
  }
  20% {
    transform: translate(-2px, 2px);
  }
  40% {
    transform: translate(-2px, -2px);
  }
  60% {
    transform: translate(2px, 2px);
  }
  80% {
    transform: translate(2px, -2px);
  }
}

@keyframes glitch-1 {
  0%, 100% {
    clip-path: polygon(0 2%, 100% 2%, 100% 5%, 0 5%);
    transform: translate(-2px);
  }
  20% {
    clip-path: polygon(0 15%, 100% 15%, 100% 20%, 0 20%);
  }
  40% {
    clip-path: polygon(0 40%, 100% 40%, 100% 45%, 0 45%);
  }
  60% {
    clip-path: polygon(0 70%, 100% 70%, 100% 75%, 0 75%);
  }
  80% {
    clip-path: polygon(0 90%, 100% 90%, 100% 95%, 0 95%);
  }
}

// 3D card flip
.hover-flip {
  perspective: 1000px;

  &__inner {
    position: relative;
    width: 100%;
    height: 100%;
    text-align: center;
    transition: transform 0.6s;
    transform-style: preserve-3d;
  }

  &:hover .hover-flip__inner {
    transform: rotateY(180deg);
  }

  &__front,
  &__back {
    position: absolute;
    width: 100%;
    height: 100%;
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
  }

  &__back {
    transform: rotateY(180deg);
  }
}

// Liquid button
.hover-liquid {
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;

  &::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
  }

  &:hover::before {
    width: 300%;
    height: 300%;
  }
}
```

### 10. Performance Optimizations

#### Virtual Scrolling for Large Lists
```jsx
// themes/custom/saho/components/performance/VirtualList.jsx
import { useRef, useState, useEffect } from 'react';
import { FixedSizeList as List } from 'react-window';
import AutoSizer from 'react-virtualized-auto-sizer';

const VirtualList = ({ items, itemHeight = 80 }) => {
  const listRef = useRef();
  const [searchTerm, setSearchTerm] = useState('');
  
  const filteredItems = items.filter(item =>
    item.title.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const Row = ({ index, style }) => {
    const item = filteredItems[index];
    
    return (
      <div style={style} className="px-4">
        <div className="flex items-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow">
          <img
            src={item.thumbnail}
            alt={item.title}
            className="w-16 h-16 rounded-lg object-cover mr-4"
            loading="lazy"
          />
          <div className="flex-1">
            <h3 className="font-semibold text-gray-900 dark:text-white">
              {item.title}
            </h3>
            <p className="text-sm text-gray-500 dark:text-gray-400">
              {item.date} • {item.category}
            </p>
          </div>
          <button className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    );
  };

  return (
    <div className="h-full flex flex-col">
      <div className="p-4">
        <input
          type="text"
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          placeholder="Search items..."
          className="w-full px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>
      
      <div className="flex-1">
        <AutoSizer>
          {({ height, width }) => (
            <List
              ref={listRef}
              height={height}
              itemCount={filteredItems.length}
              itemSize={itemHeight}
              width={width}
            >
              {Row}
            </List>
          )}
        </AutoSizer>
      </div>
    </div>
  );
};
```

### 11. Accessibility Enhancements

#### Focus Management System
```jsx
// themes/custom/saho/components/a11y/FocusManager.jsx
import { useEffect, useRef } from 'react';
import { useFocusTrap } from '@react-aria/focus';

const FocusManager = ({ isOpen, children }) => {
  const ref = useRef();
  useFocusTrap({ isDisabled: !isOpen }, ref);

  useEffect(() => {
    if (isOpen) {
      // Save current focus
      const previouslyFocused = document.activeElement;
      
      // Focus first focusable element
      const focusable = ref.current?.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      focusable?.focus();

      return () => {
        // Restore focus
        previouslyFocused?.focus();
      };
    }
  }, [isOpen]);

  return (
    <div ref={ref} role="dialog" aria-modal="true">
      {children}
    </div>
  );
};
```

## Implementation Summary

This modern redesign brings SAHO into the contemporary web landscape with:

1. **Visual Design**: Glassmorphism, gradients, and modern color schemes
2. **Interactions**: Smooth animations, micro-interactions, and gesture support
3. **Performance**: Virtual scrolling, lazy loading, and optimized assets
4. **Accessibility**: WCAG 2.1 AA compliance with focus management
5. **Search**: AI-powered search with voice and image recognition
6. **Data Visualization**: Interactive 3D timelines and dynamic charts
7. **Responsive Design**: Mobile-first with progressive enhancement
8. **Theme System**: Multiple themes including dark mode and high contrast
9. **Loading States**: Skeleton screens and progressive content loading
10. **Modern Patterns**: PWA support, offline functionality, and real-time updates

The design maintains respect for the historical content while providing a cutting-edge user experience that engages modern audiences.