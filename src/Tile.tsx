import './Tile.css'
import cover from './assets/album-covers/selling-england-by-the-pound.jpg'

type TileProps = {
  size?: number
  title?: string
  artist?: string
}

function Tile({ size = 510, title = 'Firth of Fifth - 2007 Stereo Mix', artist = 'Genesis' }: TileProps) {
  // expose the tile width as a CSS variable so CSS can compute relative sizes
  const style = { ['--tile-width' as any]: `${size}px` }

  return (
    <div className="tile" style={style as any}>
      <div className="album-cover">
        <img src={cover} alt={`${title} cover`} />
      </div>
      <div className="song-info">
        <h3 className="song-title">{title}</h3>
        <p className="artist">{artist}</p>
      </div>
    </div>
  )
}

export default Tile