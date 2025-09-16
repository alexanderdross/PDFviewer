"use client"

import { useState, useEffect, useRef } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card } from "@/components/ui/card"
import {
  ZoomIn,
  ZoomOut,
  RotateCw,
  Download,
  ChevronLeft,
  ChevronRight,
  Maximize2,
  Minimize2,
  Search,
  FileText,
} from "lucide-react"
import { cn } from "@/lib/utils"

interface PDFViewerProps {
  pdfUrl: string
  title?: string
  className?: string
  showToolbar?: boolean
  allowDownload?: boolean
  initialZoom?: number
}

export function PDFViewer({
  pdfUrl,
  title = "PDF Document",
  className,
  showToolbar = true,
  allowDownload = true,
  initialZoom = 1.0,
}: PDFViewerProps) {
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(0)
  const [zoom, setZoom] = useState(initialZoom)
  const [rotation, setRotation] = useState(0)
  const [isFullscreen, setIsFullscreen] = useState(false)
  const [searchTerm, setSearchTerm] = useState("")

  const viewerRef = useRef<HTMLIFrameElement>(null)
  const containerRef = useRef<HTMLDivElement>(null)

  // Construct PDF.js viewer URL with parameters
  const viewerUrl = `/pdfjs/web/viewer.html?file=${encodeURIComponent(pdfUrl)}`

  useEffect(() => {
    const iframe = viewerRef.current
    if (!iframe) return

    const handleLoad = () => {
      setIsLoading(false)

      // Try to communicate with PDF.js viewer
      try {
        const pdfWindow = iframe.contentWindow
        if (pdfWindow) {
          // Listen for PDF.js events
          const handleMessage = (event: MessageEvent) => {
            if (event.source !== pdfWindow) return

            const { type, data } = event.data
            switch (type) {
              case "documentloaded":
                setTotalPages(data.numPages)
                break
              case "pagechanging":
                setCurrentPage(data.pageNumber)
                break
              case "scalechanging":
                setZoom(data.scale)
                break
            }
          }

          window.addEventListener("message", handleMessage)
          return () => window.removeEventListener("message", handleMessage)
        }
      } catch (err) {
        console.warn("Could not establish communication with PDF.js viewer")
      }
    }

    const handleError = () => {
      setIsLoading(false)
      setError("Failed to load PDF document")
    }

    iframe.addEventListener("load", handleLoad)
    iframe.addEventListener("error", handleError)

    return () => {
      iframe.removeEventListener("load", handleLoad)
      iframe.removeEventListener("error", handleError)
    }
  }, [pdfUrl])

  const sendCommand = (command: string, data?: any) => {
    const iframe = viewerRef.current
    if (iframe?.contentWindow) {
      iframe.contentWindow.postMessage({ command, data }, "*")
    }
  }

  const handleZoomIn = () => {
    const newZoom = Math.min(zoom * 1.2, 5.0)
    setZoom(newZoom)
    sendCommand("zoom", { scale: newZoom })
  }

  const handleZoomOut = () => {
    const newZoom = Math.max(zoom / 1.2, 0.1)
    setZoom(newZoom)
    sendCommand("zoom", { scale: newZoom })
  }

  const handleRotate = () => {
    const newRotation = (rotation + 90) % 360
    setRotation(newRotation)
    sendCommand("rotate", { degrees: 90 })
  }

  const handlePageChange = (page: number) => {
    if (page >= 1 && page <= totalPages) {
      setCurrentPage(page)
      sendCommand("page", { pageNumber: page })
    }
  }

  const handleDownload = () => {
    const link = document.createElement("a")
    link.href = pdfUrl
    link.download = title.replace(/[^a-z0-9]/gi, "_").toLowerCase() + ".pdf"
    link.click()
  }

  const toggleFullscreen = () => {
    const container = containerRef.current
    if (!container) return

    if (!isFullscreen) {
      if (container.requestFullscreen) {
        container.requestFullscreen()
      }
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen()
      }
    }
    setIsFullscreen(!isFullscreen)
  }

  const handleSearch = () => {
    if (searchTerm.trim()) {
      sendCommand("find", { query: searchTerm, highlightAll: true })
    }
  }

  if (error) {
    return (
      <Card className={cn("flex items-center justify-center p-8", className)}>
        <div className="text-center">
          <FileText className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
          <h3 className="text-lg font-semibold text-foreground mb-2">Error Loading PDF</h3>
          <p className="text-muted-foreground">{error}</p>
        </div>
      </Card>
    )
  }

  return (
    <div
      ref={containerRef}
      className={cn(
        "relative bg-card border rounded-lg overflow-hidden",
        isFullscreen && "fixed inset-0 z-50 rounded-none",
        className,
      )}
    >
      {/* Toolbar */}
      {showToolbar && (
        <div className="flex items-center justify-between p-3 border-b bg-muted/50">
          <div className="flex items-center gap-2">
            <h3 className="font-semibold text-sm text-foreground truncate max-w-[200px]">{title}</h3>
            {totalPages > 0 && <span className="text-xs text-muted-foreground">({totalPages} pages)</span>}
          </div>

          <div className="flex items-center gap-1">
            {/* Search */}
            <div className="flex items-center gap-1 mr-2">
              <Input
                type="text"
                placeholder="Search..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                onKeyDown={(e) => e.key === "Enter" && handleSearch()}
                className="h-8 w-32 text-xs"
              />
              <Button variant="ghost" size="sm" onClick={handleSearch} className="h-8 w-8 p-0">
                <Search className="h-3 w-3" />
              </Button>
            </div>

            {/* Page Navigation */}
            <Button
              variant="ghost"
              size="sm"
              onClick={() => handlePageChange(currentPage - 1)}
              disabled={currentPage <= 1}
              className="h-8 w-8 p-0"
            >
              <ChevronLeft className="h-3 w-3" />
            </Button>

            <div className="flex items-center gap-1 text-xs">
              <Input
                type="number"
                value={currentPage}
                onChange={(e) => handlePageChange(Number.parseInt(e.target.value) || 1)}
                className="h-8 w-12 text-center text-xs"
                min={1}
                max={totalPages}
              />
              <span className="text-muted-foreground">/ {totalPages}</span>
            </div>

            <Button
              variant="ghost"
              size="sm"
              onClick={() => handlePageChange(currentPage + 1)}
              disabled={currentPage >= totalPages}
              className="h-8 w-8 p-0"
            >
              <ChevronRight className="h-3 w-3" />
            </Button>

            {/* Zoom Controls */}
            <div className="flex items-center gap-1 ml-2">
              <Button variant="ghost" size="sm" onClick={handleZoomOut} className="h-8 w-8 p-0">
                <ZoomOut className="h-3 w-3" />
              </Button>

              <span className="text-xs text-muted-foreground min-w-[3rem] text-center">{Math.round(zoom * 100)}%</span>

              <Button variant="ghost" size="sm" onClick={handleZoomIn} className="h-8 w-8 p-0">
                <ZoomIn className="h-3 w-3" />
              </Button>
            </div>

            {/* Rotate */}
            <Button variant="ghost" size="sm" onClick={handleRotate} className="h-8 w-8 p-0 ml-1">
              <RotateCw className="h-3 w-3" />
            </Button>

            {/* Download */}
            {allowDownload && (
              <Button variant="ghost" size="sm" onClick={handleDownload} className="h-8 w-8 p-0">
                <Download className="h-3 w-3" />
              </Button>
            )}

            {/* Fullscreen */}
            <Button variant="ghost" size="sm" onClick={toggleFullscreen} className="h-8 w-8 p-0">
              {isFullscreen ? <Minimize2 className="h-3 w-3" /> : <Maximize2 className="h-3 w-3" />}
            </Button>
          </div>
        </div>
      )}

      {/* PDF Viewer */}
      <div className="relative w-full h-full">
        {isLoading && (
          <div className="absolute inset-0 flex items-center justify-center bg-background/80 z-10">
            <div className="text-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
              <p className="text-sm text-muted-foreground">Loading PDF...</p>
            </div>
          </div>
        )}

        <iframe
          ref={viewerRef}
          src={viewerUrl}
          className="w-full h-full border-0"
          title={title}
          sandbox="allow-scripts allow-same-origin allow-forms"
        />
      </div>
    </div>
  )
}
