import { PDFViewer } from "@/components/pdf-viewer"

export default function Home() {
  return (
    <main className="min-h-screen bg-background">
      <div className="container mx-auto py-8">
        <div className="mb-8 text-center">
          <h1 className="text-4xl font-bold text-foreground mb-4">PDF Viewer Demo</h1>
          <p className="text-muted-foreground text-lg">Mozilla PDF.js powered viewer for WordPress integration</p>
        </div>

        <PDFViewer pdfUrl="/sample.pdf" title="Sample PDF Document" className="w-full h-[800px]" />
      </div>
    </main>
  )
}
